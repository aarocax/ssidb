# scan
Scan es un pequeño script escrito en php que busca un string en una base de datos MySql
### Uso
```sh
$ php scan.php DB_USER DB_HOST DB_NAME SEARCH_STRING
```
#### Salida
```sh
table: table_name1 - field: field_name -> 1 records
table: table_name2 - field: field_name -> 4 records
table: table_name3 - field: field_name -> 2 records
table: table_name4 - field: field_name -> 2 records
table: table_name5 - field: field_name -> 244 records