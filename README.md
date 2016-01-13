微型PHP数据库框架
==
#####使用这个数据库框架十分简单。因为只有一个文件。所以。只需要引用这个文件即可
#####PHP 数据对象 （PDO） 扩展为PHP访问数据库定义了一个轻量级的一致接口。实现 PDO 接口的每个数据库驱动可以公开具体数据库的特性作为标准扩展功能。 注意利用 PDO 扩展自身并不能实现任何数据库功能；必须使用一个 具体数据库的 PDO 驱动 来访问数据库服务。 

####要求
* PHP5.1+
* 支持的数据库有MySQL MSSQL SQLite
* 如果使用php_pdo_xxx( xxx=数据库类型 ) 你需要在php.ini中启用相关的扩展

####PHP_PDO扩展

* MySQL, MariaDB -> php_pdo_mysql
* MSSQL (Windows) -> php_pdo_sqlsrv
* MSSQL (Liunx/UNIX) -> php_pdo_dblib
* Oracle -> php_pdo_oci
* SQLite -> php_pdo_sqlite
* postgreSQL -> php_pdo_pgsql
* Sybase -> php_pdo_dblib


####PHP PDO 安装
以启用MySQL 的 php_pdo　为例

* Windows 用户

>PDO 和所有主要的驱动作为共享扩展随 PHP 一起发布，要激活它们只需简单地编辑 php.ini 文件： 
把php.ini 中找到 ;extension=php_pdo.dll 这一行，把前面的‘；’去掉即可。

>tips:这一步在 PHP 5.3及更高版本中不是必须的，对于 PDO 不再需要做为一个 DLL 文件。 
>下一步，选择其他具体数据库的 DLL 文件，然后要么在运行时用 dl() 载入，要么在 php.ini 中的 php_pdo.dll 后面启用。例如： 
* extension=php_pdo.dll
* extension=php_pdo_firebird.dll
* extension=php_pdo_informix.dll
* extension=php_pdo_mssql.dll
* extension=php_pdo_mysql.dll
* extension=php_pdo_oci.dll
* extension=php_pdo_oci8.dll
* extension=php_pdo_odbc.dll
* extension=php_pdo_pgsql.dll
* extension=php_pdo_sqlite.dll  
  
* Unix 系统上安装 PDO 

>自 PHP 5.1.0 起，PDO 和 PDO_SQLITE 驱动默认可用。对于自己选择的数据库，需要启用相应的 POD 驱动； 查阅 特定数据库的 PDO 驱动 文档获取更多此内容。 

>tips:
当以共享扩展（不推荐）构建 PDO 时，所有 PDO 驱动 必须 在 PDO 自身 之后 加载。 

>当作为一个共享模块安装 PDO 时，需要更新 php.ini 文件以便当 PHP 运行时 PDO 扩展能被自动加载。还需要在那里启用具体的数据库驱动；确保它们被列在 pdo.so 那一行之后，因为 PDO 必须在具体的 数据库扩展被载入前初始化。如果静态地构建 PDO 和 具体数据库扩展，可以跳过此步。 

>extension=pdo.so 


####配置使用
