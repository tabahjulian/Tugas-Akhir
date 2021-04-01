import arcpy
arcpy.CheckOutExtension("Network")
arcpy.env.overwriteOutput = True
tanggal = raw_input("Masukkan Tanggal(YYYY-MM-DD):   ")
waktu = raw_input("Pilih Waktu (00/08/16):   ")
tanggalWaktu = str.replace(tanggal, "-", "") + waktu
forQuery = "({},{})".format(tanggal, waktu)
arcpy.env.workspace = r"D:\TA\Demo\fix"
# # masukkin data permintaan
arcpy.MakeQueryLayer_management("Database Connections\Tabah.sde", "permintaan",
                                "select * from postgres.permintaan where nopermintaan like '{}%'".format(tanggalWaktu))
# # masukkin data rute
arcpy.MakeTableView_management(
    "Database Connections\Tabah.sde\postgis_25_sample.postgres.jemputtruk", "route", """ "tglwaktu" = '{}' """.format(forQuery))
# # ngebuat layer VRP biar bisa diolah per sub layernya
vrp = arcpy.na.MakeVehicleRoutingProblemLayer(
    r"D:\TA\Demo\fix\jalan.nd", "VRP", "Time", "Length")
vrpLayer = vrp.getOutput(0)
subLayer = arcpy.na.GetNAClassNames(vrpLayer)
ordersLayer = subLayer["Orders"]
depotsLayer = subLayer["Depots"]
routesLayer = subLayer["Routes"]
# # D:\TA\Demo\Tabah\Jalan.ND
# # MASUKKIN ORDER
# # mapping permintaan
orderCandidateFields = arcpy.ListFields("permintaan")
orderFieldMappings = arcpy.na.NAClassFieldMappings(vrpLayer, ordersLayer,
                                                   False, orderCandidateFields)
orderFieldMappings["Name"].mappedFieldName = "namapelanggan"
orderFieldMappings["PickupQuantities"].mappedFieldName = "volume"
arcpy.na.AddLocations(vrpLayer, ordersLayer,
                      "permintaan", orderFieldMappings, "")

# # mapping tps
arcpy.MakeFeatureLayer_management("tpsBanut.shp", "tpsBanut")
tpsCandidateFields = arcpy.ListFields("tpsBanut")
tpsFieldMappings = arcpy.na.NAClassFieldMappings(
    vrpLayer, ordersLayer, False, tpsCandidateFields)
tpsFieldMappings["Name"].mappedFieldName = "Nama_TPS"
tpsFieldMappings["AssignmentRule"].defaultValue = 5
arcpy.na.AddLocations(vrpLayer, ordersLayer,
                      "tpsBanut", tpsFieldMappings, "")

# # MASUKKIN DEPOT
# # mapping pool
arcpy.MakeFeatureLayer_management("pool.shp", "pool")
depotFieldMappings = arcpy.na.NAClassFieldMappings(vrpLayer, depotsLayer)
depotFieldMappings["Name"].defaultValue = "Pool"
arcpy.na.AddLocations(vrpLayer, depotsLayer, "Pool", depotFieldMappings, "")

# # Masukkin Routes
# # Mapping Truck
truckCandidateFields = arcpy.ListFields("route")
routeFieldMapping = arcpy.na.NAClassFieldMappings(
    vrpLayer, routesLayer, False, truckCandidateFields)
routeFieldMapping["Name"].mappedFieldName = "nopoltruk"
routeFieldMapping["Capacities"].mappedFieldName = "volume"
routeFieldMapping["StartDepotName"].mappedFieldName = "asal"
routeFieldMapping["EndDepotName"].mappedFieldName = "asal"
routeFieldMapping["AssignmentRule"].mappedFieldName = "assignmentrule"
arcpy.na.AddLocations(vrpLayer, routesLayer, "route", routeFieldMapping, "")

# # solve
arcpy.na.Solve(vrpLayer, "SKIP", "CONTINUE")
vrpRoutesLayer = arcpy.mapping.ListLayers(vrpLayer)[6]
vrpOrderLayer = arcpy.mapping.ListLayers(vrpLayer)[1]
arcpy.FeaturesToJSON_conversion(vrpOrderLayer, r"D:\RnD\WAPP\Apache24\htdocs\json\permintaan\{}.json".format(tanggalWaktu),
                                "FORMATTED", "NO_Z_VALUES", "NO_M_VALUES", "GEOJSON")
arcpy.FeaturesToJSON_conversion(vrpRoutesLayer, r"D:\RnD\WAPP\Apache24\htdocs\json\rute\{}.json".format(
    tanggalWaktu), "NOT_FORMATTED", "NO_Z_VALUES", "NO_M_VALUES", "NO_GEOJSON")

print "Berhasil!"
