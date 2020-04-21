math.randomseed(os.time())
number =  math.random(1000000)

wrk.method = "POST"
wrk.body = '{ "paymentOrderId": "' .. tostring(number) .. '" , "serviceCatalogType": "ECOSYSTEM", "serviceExternalId": "76-897787564654-A", "customerId": "190471a9-b5fb-4cbe-9b24-891c07f0c4c5", "clientKeyType": "ERIB_ID", "clientKey": "015-67868-0834", "packetId": "fd37aaf1-cd7e-4cb1-8593-0d6e9d797171-9", "paySystemType": "EPS", "paySystemTransactionId": 54345656576, "paymentDate": "2019-01-01T13:12:34.231+0300", "paymentExpired": "2020-01-01T13:12:34.231+0300" }'
wrk.headers["Content-Type"] = "application/json"
