Clase para hacer consultas con estamentos de MySQLi
<hr>
### Índice
**[Iniciar](#iniciar)**<br/>
**[Insert](#insert)**<br/>
**[Select](#select)**<br/>
**[Where](#where)**<br/>
**[Update](#update)**<br/>
**[Delete](#delete)**<br/>
**[Consultas Simples](#consultas-simples)**<br/>
**[Order By](#order-by)**<br/>
**[Group By](#group-by)**<br/>
**[Join](#join)**<br/>
**[Consultas Complejas](#consultas-complejas)**

### Iniciar

```php
require_once ('Database.php');
$db = new Database('server', 'user', 'password', 'database');
```

### Insert
```sql
INSERT INTO EMPLEADOS (NOMBRE,APELLIDO,FECHA_NACIMIENTO, EMAIL, ROL) VALUES ('Nuevo','Empleado','1990-02-16','nuevo@empleado.com',2)
```

```php
    if ($db->insert([
          "NOMBRE" => "Nuevo",
          "APELLIDO" => "Empleado",
          "FECHA_NACIMIENTO" => "1990-02-16",
          "EMAIL" => "nuevo@empleado.com",
          "ROL" => 2
      ])
          ->into("EMPLEADOS")
          ->exec()
      ) {
          echo("Nuevo empleado insertado correctamente");
      }
```



### Select

#### Simple
```sql
SELECT NOMBRE FROM EMPLEADOS
```

```php
    $db->select("NOMBRE")
        ->from("EMPLEADOS")
        ->then(function ($empleados) {
            foreach ($empleados as $empleado) {
                echo $empleado['NOMBRE'];
            }
        });
```
#### Almacenando los datos
```sql
SELECT NOMBRE FROM EMPLEADOS
```

```php
$nombre = $db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->storage();
var_dump($nombre);
```
#### Almacenando los datos tras modificarlos
```sql
SELECT NOMBRE, EMAIL FROM EMPLEADOS WHERE ID=4
```

```php
$nombreYemail = $db->select(["NOMBRE", "EMAIL"])
    ->from("EMPLEADOS")
    ->where(["ID" => 4])
    ->then(function ($empleados) {
        return $empleados[0]["NOMBRE"] . " con email " . $empleados[0]["EMAIL"];
    });
var_dump($nombreYemail);
```

#### Limit
```sql
SELECT NOMBRE FROM EMPLEADOS LIMIT 5
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->limit(5)
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```

```sql
SELECT NOMBRE FROM EMPLEADOS LIMIT 2,5
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->limit(2,5)
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```

### Where

#### Simple
```sql
SELECT NOMBRE FROM EMPLEADOS WHERE ID=5
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->where(['ID' => 5])
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```
<hr/>
```sql
SELECT NOMBRE FROM EMPLEADOS WHERE ID<=5
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->where(['ID' => ['<=', 5]])
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```

#### Compuesto

##### AND
```sql
SELECT NOMBRE FROM EMPLEADOS WHERE ID<=5 AND DATE(FECHA_NACIMIENTO)<'1970-01-01'
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->where([
        'ID' => ['<=', 5],
        'DATE(FECHA_NACIMIENTO)' => ['<', '1970-01-01']
    ])
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```
##### OR
```sql
SELECT NOMBRE FROM EMPLEADOS WHERE ID<=5 OR DATE(FECHA_NACIMIENTO)>'1994-01-01'
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->where(['ID' => ['<=', 5]])
    ->orWhere(['DATE(FECHA_NACIMIENTO)' => ['>', '1994-01-01']])
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```


### Update
```sql
UPDATE EMPLEADOS SET EMAIL='email@dementira.com' WHERE EMAIL='nuevo@empleado.com'
```

```php
    if ($db->update("EMPLEADOS")
        ->set(["EMAIL" => "email@dementira.com"])
        ->where(["EMAIL" => "nuevo@empleado.com"])
        ->exec()
    ) {
        echo("todos los nuevo@empleado.com ahora son email@dementira.com");
    }
```

### Delete
```sql
DELETE FROM EMPLEADOS WHERE EMAIL='email@dementira.com' AND NOMBRE='Nuevo'
```

```php
    if ($db->delete("EMPLEADOS")
        ->where([
            "EMAIL" => "email@dementira.com",
            "NOMBRE" => "Nuevo"
        ])
        ->exec()
    ) {
        echo("todos los empleados Nuevo con email email@dementira.com han sido eliminados");
    }
```

### Consultas Simples
```sql
CREATE TABLE PERSONS(PersonID int,LastName varchar(255),FirstName varchar(255),Address varchar(255),City varchar(255))
```

```php
    if ($db->simpleQuery("CREATE TABLE PERSONS(PersonID int,LastName varchar(255),FirstName varchar(255),Address varchar(255),City varchar(255));"))
        echo "Tabla PERSONS creada";
```

```sql
DROP TABLE PERSONS
```

```php
    if ($db->simpleQuery("DROP TABLE PERSONS;"))
        echo "Tabla Persons borrada";
```

### Order By
```sql
SELECT NOMBRE FROM EMPLEADOS ORDER BY DATE(FECHA_NACIMIENTO)
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->orderBy("FECHA_NACIMIENTO")
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```

```sql
SELECT NOMBRE FROM EMPLEADOS ORDER BY DATE(FECHA_NACIMIENTO) DESC
```

```php
$db->select("NOMBRE")
    ->from("EMPLEADOS")
    ->orderBy("FECHA_NACIMIENTO", "DESC")
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            echo $empleado['NOMBRE'];
        }
    });
```

### Group By
```sql
SELECT COUNT(*) FROM EMPLEADOS GROUP BY ROL
```

```php
$num = $db->select("COUNT(*)")
    ->from("EMPLEADOS")
    ->groupBy("ROL")
    ->storage();
var_dump($num);
```

### Join
```sql
SELECT COUNT(*),DESCRIPCION FROM EMPLEADOS INNER JOIN ROLES ON EMPLEADOS.ROL=ROLES.ID_ROL GROUP BY ID_ROL
```

```php
$db->select(["COUNT(*)", "DESCRIPCION"])
    ->from("EMPLEADOS")
    ->join([["EMPLEADOS" => "ROL"], ["ROLES" => "ID_ROL"]])
    ->groupBy("ID_ROL")
    ->then(function ($datos) {
        foreach ($datos as $dato) {
            echo "[" . $dato['DESCRIPCION'] . "/" . $dato['COUNT(*)'] . "]-";
        }
    });
```


### Consultas Complejas
```sql
SELECT ID, NOMBRE, APELLIDO, DESCRIPCION FROM EMPLEADOS
  INNER JOIN ROLES ON EMPLEADOS.ROL=ROLES.ID_ROL
  WHERE ID<10 AND NOMBRE='Carly'
  OR DATE(FECHA_NACIMIENTO)>'1990-01-01' AND NOMBRE='Richard'
  OR DESCRIPCION='INVITADO' AND NOMBRE='Brooke'
  ORDER BY NOMBRE
  LIMIT 1, 2
```

```php
$empleado = $db->select(["ID", "NOMBRE", "APELLIDO", "DESCRIPCION"])
    ->from("EMPLEADOS")
    ->join([["EMPLEADOS" => "ROL"], ["ROLES" => "ID_ROL"]])
    ->where([
        "ID" => ["<", 10],
        "NOMBRE" => "Carly"
    ])
    ->orWhere([
        "DATE(FECHA_NACIMIENTO)" => [">", "1990-01-01"],
        "NOMBRE" => "Richard"
    ])
    ->orWhere([
        "DESCRIPCION" => "INVITADO",
        "NOMBRE" => "Brooke"
    ])
    ->orderBy("NOMBRE")
    ->limit(1, 2)
    ->then(function ($empleados) {
        foreach ($empleados as $empleado) {
            if ($empleado['ID'] * 3 > 200)
                return $empleado;
        }

    });
var_dump($empleado);
```
