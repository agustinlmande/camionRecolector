1. Levantar el servidor PHP

En tu carpeta del proyecto (donde están Vehiculos.php y api.php):

php -S localhost:8000


Deja esa ventana abierta.

2. Probar API con PowerShell
➤ POST (crear camión)
Invoke-RestMethod -Uri "http://localhost:8000/api.php" `
  -Method Post `
  -ContentType "application/json" `
  -Body '{"tipo":"Camion","placa":"XYZ123","capacidad":7000,"conductor":"Pedro"}'

➤ GET (todos los vehículos)
Invoke-RestMethod -Uri "http://localhost:8000/api.php" -Method Get

➤ GET (por ID)
Invoke-RestMethod -Uri "http://localhost:8000/api.php?id=EL_ID_GENERADO" -Method Get

➤ PUT (actualizar un camión)
Invoke-RestMethod -Uri "http://localhost:8000/api.php?id=EL_ID_GENERADO" `
  -Method Put `
  -ContentType "application/json" `
  -Body '{"tipo":"Camion","placa":"ABC999","capacidad":8000,"conductor":"Luis"}'

➤ DELETE (eliminar un camión)
Invoke-RestMethod -Uri "http://localhost:8000/api.php?id=EL_ID_GENERADO" -Method Delete
