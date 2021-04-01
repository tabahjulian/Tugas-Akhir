<?php
    $conn = pg_connect("host=localhost port=8686 user=postgres password=postgres dbname=postgis_25_sample");
    if(isset($_POST["submit"])){
        $idTps = $_POST["idTps"];
        $namaTps = $_POST["listTps"];
        $tanggal = $_POST["tglAmbil"];
        $waktu = $_POST["waktuAmbil"];
        $volume = $_POST["volumeSampah"];
        $Permintaan = "{$tanggal}{$waktu}{$idTps}";
        $nopermintaan = str_replace("-", "", $Permintaan);
        $tglWaktu = "{$tanggal}{$waktu}";
        $forQuery = str_replace("-", "", $tglWaktu);
            // cek masih tersedia atau engga
            if (pg_fetch_result(pg_query($conn,"SELECT SUM(volume) from postgres.permintaan WHERE \"nopermintaan\" LIKE '{$forQuery}%'"),0,0) + $volume <= 252){
                // kalau belum pernah ada yang minta
                if (pg_affected_rows(pg_query($conn, "SELECT * FROM postgres.\"jemputtruk\" WHERE \"tglwaktu\" = '({$tanggal},{$waktu})' ")) == 0)  {
                    if($volume <= 6){
                        //tambah truk yang 6
                        pg_query($conn,"INSERT INTO postgres.\"permintaan\" SELECT '$nopermintaan','$idTps','$namaTps','$tanggal','$waktu','$volume', \"shape\" from postgres.\"pelanggan\" WHERE \"kodepelang\" = '$idTps' ");
                        //masukkin ke database
                        pg_query($conn,"UPDATE postgres.\"jemputtruk\" 
                        SET \"assignmentrule\" = 'true' 
                        WHERE \"idjemput\" = (select \"idjemput\" 
                        from postgres.\"jemputtruk\" 
                        WHERE \"tglwaktu\" = '({$tanggal},{$waktu})'
                        and \"assignmentrule\" = 'false' order by volume asc LIMIT 1)");
                        }
                        //tambah truk yang 12
                        else {
                            pg_query($conn,"INSERT INTO postgres.\"permintaan\" SELECT '$nopermintaan','$idTps','$namaTps','$tanggal','$waktu','$volume', \"shape\" from postgres.\"pelanggan\" WHERE \"kodepelang\" = '$idTps' ");

                            pg_query($conn,"UPDATE postgres.\"jemputtruk\" 
                            SET \"assignmentrule\" = 'true' 
                            WHERE \"idjemput\" = (select \"idjemput\" 
                            from postgres.\"jemputtruk\" 
                            WHERE \"tglwaktu\" = '({$tanggal},{$waktu})'
                            and \"assignmentrule\" = 'false' order by volume desc LIMIT 1)");

                        }

                }
                // kalau udah pernah ada yang minta
                else {
                    //cek masih cukup / engga
                    //kalau cukup langsung masukkin datanya
                    if ((pg_fetch_result(pg_query($conn,"SELECT SUM(volume) from postgres.permintaan WHERE \"nopermintaan\" LIKE '{$forQuery}%'"),0,0) + $volume)
                    <=
                    pg_fetch_result(pg_query($conn,"SELECT SUM(volume) from postgres.\"jemputtruk\" WHERE \"tglwaktu\"
                    = '({$tanggal},{$waktu})' and \"assignmentrule\" = 'true'"),0,0) ) {
                        pg_query($conn,"INSERT INTO postgres.\"permintaan\" SELECT '$nopermintaan','$idTps','$namaTps','$tanggal','$waktu','$volume', \"shape\" from postgres.\"pelanggan\" WHERE \"kodepelang\" = '$idTps' ");
                    }
                    //kalau gacukup
                    else {
                        if($volume <=6 ){
                        //tambah truk yang 6
                        pg_query($conn,"UPDATE postgres.\"jemputtruk\" 
                        SET \"assignmentrule\" = 'true' 
                        WHERE \"idjemput\" = (select \"idjemput\" 
                        from postgres.\"jemputtruk\" 
                        WHERE \"tglwaktu\" = '({$tanggal},{$waktu})'
                        and \"assignmentrule\" = 'false' order by volume asc LIMIT 1)");
                        //masukkin ke database
                        pg_query($conn,"INSERT INTO postgres.\"permintaan\" SELECT '$nopermintaan','$idTps','$namaTps','$tanggal','$waktu','$volume', \"shape\" from postgres.\"pelanggan\" WHERE \"kodepelang\" = '$idTps' ");}
                        //tambah truk yang 12
                        else {
                            pg_query($conn,"UPDATE postgres.\"jemputtruk\" 
                            SET \"assignmentrule\" = 'true' 
                            WHERE \"idjemput\" = (select \"idjemput\" 
                            from postgres.\"jemputtruk\" 
                            WHERE \"tglwaktu\" = '({$tanggal},{$waktu})'
                            and \"assignmentrule\" = 'false' order by volume desc LIMIT 1)");

                            pg_query($conn,"INSERT INTO postgres.\"permintaan\" SELECT '$nopermintaan','$idTps','$namaTps','$tanggal','$waktu','$volume', \"shape\" from postgres.\"pelanggan\" WHERE \"kodepelang\" = '$idTps' "); 
                        }
                    }
                }
            }
            else {
                echo "Maaf truk tidak tersedia pada tanggal {$tanggal}";
            }     
    }

?>


<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <title>Pelanggan</title>
    <script src='https://js.arcgis.com/4.13'></script>
    <link rel="stylesheet" href="https://js.arcgis.com/4.13/esri/css/main.css">
</head>

<body>
    <script>
        let map;
        let mapview;
        require(["esri/Map",
                "esri/views/MapView",
                "esri/request",
                "esri/layers/GeoJSONLayer",
                "esri/widgets/Popup",
                "esri/widgets/BasemapToggle"
            ],
            function (Map, MapView, request, GeoJSONLayer, Popup, BasemapToggle) {
                map = new Map({
                    basemap: "streets-navigation-vector"
                })
                mapview = new MapView({
                    container: "mapview",
                    map: map,
                    center: [107.61614836429119, -6.879847681291412],
                    scale: 50000
                })

                let toggle = new BasemapToggle({
                    view: mapview,
                    nextBasemap: "satellite"
                });
                mapview.ui.add(toggle, "top-right");

                let urlTps = "/json/pelanggan.json";
                let options = {
                    responseType: "json"
                };
                request(urlTps, options)
                    .then(function (response) {
                        let result = response.data;
                        let listTps = document.getElementById("listTps");
                        let listId = document.getElementById("idTps");
                        listTps.addEventListener("change", function () {
                            let tpsCoord = result.features[this.selectedIndex - 1].geometry.coordinates;
                            let namaTps = result.features[this.selectedIndex - 1].properties.namaPelang;
                            document.querySelector('#idTps').options[this.selectedIndex].selected =
                                true;
                            mapview.goTo({
                                center: tpsCoord,
                                zoom: 15
                            })
                            mapview.popup.open({
                                title: namaTps,
                                location: tpsCoord
                            });
                            mapview.popup.content = "LONGITUDE : " + tpsCoord[0] + "  LATITUDE : " +
                                tpsCoord[1]
                        })
                        listId.addEventListener("change", function () {
                            let tpsCoord = result.features[this.selectedIndex - 1].geometry.coordinates;
                            let namaTps = result.features[this.selectedIndex - 1].properties.namaPelang;
                            document.querySelector('#listTps').options[this.selectedIndex].selected =
                                true;
                            mapview.goTo({
                                center: tpsCoord,
                                zoom: 15
                            })
                            mapview.popup.open({
                                title: namaTps,
                                location: tpsCoord
                            });
                            mapview.popup.content = "LONGITUDE : " + tpsCoord[0] + "  LATITUDE : " +
                                tpsCoord[1]
                        })
                        for (let i = 0; i < result.features.length; i++) {
                            let optionNama = document.createElement("option")
                            let optionId = document.createElement("option")
                            optionNama.textContent = result.features[i].properties.namaPelang
                            optionId.textContent = result.features[i].properties.kodePelang
                            idTps.appendChild(optionId)
                            listTps.appendChild(optionNama)
                        }
                    })

                // Tambah layer TPS
                let layerTps = new GeoJSONLayer({
                    url: urlTps
                })
                map.add(layerTps);
            });
    </script>
    <header class="navbar navbar-expand navbar-dark bg-dark flex-column flex-md-row bd-navbar"
        style="border-bottom: 5px inset orange;">
        <div class="container">
            <a class="navbar-brand">Tabah Juliansah</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-item nav-link" href="index.html">Home </a>
                    <a class="nav-item nav-link active" href="#">Pelanggan</a>
                    <a class="nav-item nav-link" href="pengemudi.php">Pengemudi</a>
                </div>
            </div>
    </header>
    <div class="container-fluid row text-warning" style="height: 92vh; ">
        <div id="mapview" class="container col-9">
        </div>
        <div class="container col-3 pt-3 px-4" style="border-left: 2px inset lightskyblue">
            <form method="POST">
                <div class="form-group">
                    <label for="idTps">Kode Pelanggan</label>
                    <select class="form-control custom-select" name="idTps" id="idTps">
                        <option selected>Pilih Kode Pelanggan Anda</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="listTps">Nama Pelanggan</label>
                    <select class="form-control custom-select" name="listTps" id="listTps">
                        <option selected>Pilih Nama Anda</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tglAmbil">Tanggal Penjemputan</label>
                    <input type="date" name="tglAmbil" class="form-control" id="tglAmbil">
                </div>
                <div class="form-group">
                    <label for="waktuAmbil">Waktu Penjemputan</label>
                    <select class="form-control custom-select" name="waktuAmbil" id="waktuAmbil">
                        <option selected>Pilih Waktu Penjemputan</option>
                        <option value="00"> 00:00 - 08.00</option>
                        <option value="08"> 08:00 - 16.00</option>
                        <option value="16"> 16:00 - 24.00</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="volumeSampah">Volume Sampah</label>
                    <input type="number" name="volumeSampah" min="1" max="12" placeholder="Masukkan Volume Sampah Anda"
                        class="form-control">
                </div>
                <div class="form-group float-right">
                    <button type="submit" name="submit" class="btn btn-warning">Submit</button>
                </div>
            </form>
        </div>
    </div>

