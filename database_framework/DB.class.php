<?php

/**
 *
 * ass database framework
 * http://www.smaij.com
 * Version 1.0.0
 *
 * Copyright 2015,
 *
 * Released under the MIT license
 **/

class DB{

  // General (必须)
  //数据库类型
  protected $database_type;

  //数据库字符集
  protected $charset;

  //数据库名
  protected $database_name;

  //用户名
  protected $username;

  //密码
  protected $password;


  //optional (可选)

  //数据库端口
  protected $port;

  //数据表前缀
  protected $prefix;

  //option
  protected $option = array();

  //variable (变量)
  protected $logs   = array();

  //Mysql or MariaDB with unix_socket
  protected $socket;

  //debug_mode (调试模式)
  protected $debug_mode = false;

  /**
   *
   * 构造函数
   *  (连接数据库)
   *
   *
   * @param $options array
   * 传递一个数据库设置组给class
   *
   **/
  public function __construct( $options=array() ){
    
   try{
  

      if( is_array( $options ) ){
        //判读数组
        //遍历数组
        foreach( $options as $option=> $value ){
           //赋值到对应option中去
           $this->$option = $value;
        }
      }else{
        //不是数组退出
        //
        return false;
      
      }

      /**
       *
       * 设置端口
       * 如果设置了端口
       *
       * is_int(检测变量是否是整数)
       **/

      if( isset( $this->port ) && is_int($this->port * 1) ){
        $port = $this->port;
      }

      //判断是否有设置port 存放到 is_port
      $is_port = isset( $this->port );

      //将 type 转换为小写字母
      $type = strtolower( $this->database_type );

      //pdo 连接标识
      $dsn = '';

      switch( $type ){
        
        case 'mysql'://拼接sql的 PDO dsn

          if( $this->socket ){
            $dsn = $type.':unix_socket='.$this->socket.';dbname='.$this->database_name;
          }else{
            //mysql:hostname;port=3306;dbname=test
            $dsn = $type.':host='.$this->server.( $is_port ? ';port='.$port : '' ).';dbname='.$this->database_name;
          }

          //让MySQL使用标准标识符
          $commands[] = 'SET SQL_MODE=ANSI_QUOTES';

          break;
      
          //其他的数据库支持日后了解并使用过了再写********************************************
      
      }

      //命令组
      $commands = array();


      //设置字符集
      if( $this->charset ){
        $commands[] = "SET NAMES '".$this->charset."'";
      }

      /**
       * 设置数据表前缀
       * 如果用户初始化时传递的options数组中包含
       * prefix 值。
       *  则 设置prefix
       *
       */
      if( isset( $options['prefix'] ) ){
        $this->prefix = $options['prefix'];
      }


      //实例化PDO
      $this->pdo = new PDO(
        $dsn,
        $this->username,
        $this->password,
        $this->option
      );

      //运行commands
      foreach( $commands as $value ){
        $this->pdo->exec( $value );
      }

    }catch (PDOException $e) {
      throw new Exception( $e->getMessage() );
    }
  }

  //原生方法

  /**
   *
   * query
   * 执行SQL方法
   *
   **/

  public function query( $sql ){

    $this->_debug( $sql );

    //写入日志
    $this->_logs( $sql );

    //执行SQL
    return $this->pdo->query( $sql );

  }


  /**
   *
   * quote
   * 可以把输入字符串中带有引号的地方进行特殊字符转义
   * 用于加强执行sql语句的安全
   *
   * @param $string string 需要转义的字符串
   * return 转义之后的字符串
   *
   **/
  public function quote( $string ){

    return $this->pdo->quote( $string );
  
  }

  //end ( 原生方法 )
  //
  //select 查询方法


  /**
   *
   * select
   * 查询方法
   *
   * @param $table   string  表名
   * @param $join    string  多表查询,不使用可以忽略.
   * @param $columns string  default null  要查询的字段名
   * @param $where   string  default null  查询的条件
   *
   * return array 返回查询结果
   *
   **/

  public function select( $table , $join , $columns = null , $where = null ){

    //在select_context方法进行处理
    $query = $this->query( 
      $this->select_context(
        $table,
        $join,
        $columns,
        $where  
      )
    );

    //返回query
    return $query ? $query->fetchAll(
      ( is_string( $columns ) && $columns != '*' ) ? PDO::FETCH_COLUMN : PDO::FETCH_ASSOC
    ):false;
  
  }

   //**** select_context

  /**
   *
   * insert
   * 插入记录
   *
   * @param $table  string  表名
   * @param $data   array   插入到表里的信息数组
   *
   * return 插入的数据条数
   *
   **/
  public function insert( $table,$datas ){

    //插入的数据返回影响条数
    $insert_result = 0;

    //确认信息数组是array
    if( is_array( $datas ) ){

      //检测关联或索引数组
      if( !isset( $datas[ 0 ] ) ){

        //关联数组
        //转换为索引数组
        $datas = array( $datas );
      
      }
      foreach( $datas as $data ){

        /**
         *
         * @param   array   $values  插入数据库的值 
         * @param   array   $columns  插入数据库的字段
         *
         **/

        $values  = array();
        $columns = array();

        if( is_array( $data ) ){

          //遍历三维数组
          foreach( $data as $key => $value ){

            //将key 设置为插入的数据库字段
            //给每个key添加双引号
            array_push( $columns , $key );

            //判断插入数据库值的类型
            switch( gettype( $value ) ){
              
              case  'NULL':
              case  'null':
                $values[] = 'NULL';
                break;
              
              case  'array':
                preg_match("/\(JSON\)\s*([\w]+)/i", $key, $column_match);

                $values[] = isset( $column_match[ 0 ] ) ?
                            $this->quote( json_encode( $value ) ):
                            $this->quote( serialize( $value ) );
                break;

              case  'boolean':
                $values[] = $value ? '1' : '0';
                break;

              case  'integer':
              case  'double' :
              case  'string' :
                $values[] = $this->fn_quote( $key,$value );
                break;
            }

          }
          
          $sql = 'INSERT INTO `'.$this->prefix .$table .'` ('. implode( ',' , $columns ).') VALUES('. implode( ',' , $values ) .');';
          if( $this->exec( $sql ) ){
            $insert_result += 1;
          }
        }
      }

      return $insert_result;
    }
  }


  /**
   *
   * delete
   * 删除记录
   *
   * @param $table  string  表名
   * @param $where  array   删除的条件
   *
   * return 成功与否
   *
   *用法: 
   *
   **/

   public function delete( $table,$where ){

     $sql = 'DELETE FROM `'.$this->prefix .$table . '` '.$this->where_clause( $where );
     echo $sql;
   }

  //end ( select 查询方法 )

  /**
   *
   * exec
   * 执行一条 SQL 语句，并返回受影响的行数
   *
   * @param $sql string sql语句
   * return 受影响的行数
   *
   **/
  public function exec( $sql ){

    $this->_debug( $sql );
    $this->_logs( $sql );

    return $this->pdo->exec( $sql );
  
  }

  /**
   *
   * 为keyt 添加双引号
   * &
   * 过滤特殊字符串
   *
   *  ( JSON or #  = '')
   *  ( . = "." )
   *
   **/
	public function column_quote($string)
  {
    /**
     *
     * 过滤 @param $string 中的所有# 或 (JSON) 字符
     * 将   @param $strubg 中的. 替换成 "."
     *
     **/
		return '"' . str_replace('.', '"."', preg_replace('/(^#|\(JSON\)\s*)/', '', $string)) . '"';
	}

  /**
   *
   * array_quote
   *
   * 将数组中的每一个value 遍历进行转义
   *
   * @param $array array 需要转义的字符串组成的array
   * return array 返回重组的array
   *
   **/
  protected function array_quote( $array ){

    //临时数组变量
    //用于存放转义过后的新字符串
    $temp = array();

    if( is_array( $array ) ){
      
      foreach( $array as $value ){

        //字符转义
        $temp[] = is_int( $value )? $value : $this->quote( $value );

      }

      return implode( $temp , ',' );
    }
  
  }


  /**
   *
   *? 241
   **/
  protected function inner_connector( $data,$connector,$outer_connector ){

    $haystack = array();
    
    if( empty( $connector ) ){
      $connector = '';
    }

    if( is_array( $data )){
      foreach( $data as $value ){
        $haystack[] = '('. $this->data_implode( $value , $connector ).')';
      }

      return implode( $outer_connector . ' ' , $haystack );
    }
  
  }


  /**
   * 
   * 识别使用SQL函数来处理数据。
   *  （在字段前加入#号）
   *
   **/

  protected function fn_quote( $column,$string ){

    /**
     *
     * 匹配 column 的第一个字符串是否为 '#'
     * 匹配 函数
     *
     * @param $column string  插入数据库的字符名
     * @param $string string  插入数据库的数据
     *
     * 如果$column 首字母是# 而且 插入的$string 中格式是大写A-Z0-9_和一对小括号
     * 组成。那么就认为他是需要处理的函数, 返回原函数名称，
     * 否则将$string当字符串处理
     *
     **/

    return  ( strpos( $column , '#' ) === 0 && preg_match( '/^[A-Z0-9\_]*\([^)]*\)$/',$string ) )?
            $string:
            $this->quote( $string );
  }

  /**
   *
   * where_clause
   * 拼接条件
   *
   * @param $where array 传入的条件
   *
   * return 转义的字符串输出
   *
   **/

   public function where_clause( $where ){

     //定义where语句
     $where_clause = '';
     if( is_array( $where )){

       //获取array的key
       $where_key = array_keys( $where );

       //匹配AND 开始的字符串
       $where_AND = preg_grep( "/^AND\s*#?$/i" , $where_key );
       //匹配OR 开始的字符串
       $where_OR  = preg_grep( "/^OR\s*#?$/i"  , $where_key );

       $single_condition = array_diff_key( $where, array_flip(
          explode( ' ', 'AND OR GROUP HAVING LIMIT LIKE MATCH')
       ));

       if( $single_condition != array() ){
         $where_clause = ' WHERE '.$this->data_implode( $single_condition , '' );
       }

       //如果是 OR 语句
       if( !empty( $where_OR )){
       
         $value = array_values( $where_OR );       
         $where_clause = ' WHERE '.$this->data_implode( $where[ $value[ 0 ] ],' OR ' );
       }

       //如果是 AND 语句
       if( !empty( $where_AND )){
    
         $value = array_values( $where_AND );       
         $where_clause = ' WHERE '.$this->data_implode( $where[ $value[ 0 ] ],' AND ' );
       }

       //使用match 全文检索
       if( isset( $where[ 'MATCH' ] ) ){

         $MATCH = $where[ 'MATCH' ];

         //如果这个 $MATCH 是个数组，并且包含有columns 和 keyword 两个值
         if( (isset( $MATCH )) && isset( $MATCH[ 'columns' ] , $MATCH[ 'keyword' ]) ){

           //拼接match
           $where_clause .= ( $where_clause != '' ? 'AND' : 'WHERE' ).
                            ' MATCH("' . str_replace( '.', '"."', implode( $MATCH[ 'columns' ], '","')).
                            '") AGAINST ('.$this->quote( $MATCH[ 'keyword' ] ). ')';
         }
       
       }

       /**
        *
        * 使group by 排序
        * 
        **/
       if( isset( $where[ 'GROUP' ] ) ){

         //如果GROUP 
         $where_clause .= ' GROUP BY '.$this->column_quote( $where[ 'GROUP' ] );

         //如果是HAVING
         if( (isset( $where[ 'HAVING' ] )) ){

           $where_clause .= ' HAVING '.$this->data_implode( $where['HAVING'] , ' AND'); 
         }
       
       }

       if( isset( $where[ 'ORDER' ] ) ){
         //匹配字符desc asc
         $rsort = '/(^[A-Za-z0-9_\-\.]*)(\s(DESC|ASC))?/';
         //排序方式
         $ORDER = $where[ 'ORDER' ];
       
         //如果ORDER是数组
         if( is_array( $ORDER ) ){
           if(

             isset( $ORDER[ '1' ]) &&
             is_array( $ORDER[ 1 ])
           ){

             //FIELD
             $where_clause .= ' ORDER BY FIELD('.$this->column_quote( $ORDER[ 0 ]).' , '.$this->array_quote( $ORDER[ 1 ]).')';
           }else{

             $stack = array();
             foreach( $ORDER as $column ){

               //match 
               preg_match( $rsort , $column , $order_match );
               array_push( $stack , '"' . str_ireplace( '.' , '"."', $order_match[ 1 ]).'"'.(isset( $order_match[ 3 ] ))?' '.$order_match[ 3 ]:'' );
             }
           }
         }
       }
     
       if( isset( $where[ 'LIMIT' ] ) ){

         $LIMIT   = $where['LIMIT'];

         //如果是数字
         if( is_numeric( $LIMIT )){

           //拼接单个数字
           $where_clause .= ' LIMIT '.$LIMIT;
         }    
  
         if( is_array( $LIMIT ) && is_numeric( $LIMIT[ 0 ]) && is_numeric( $LIMIT[1] ) ){

           //拼接多个数字
           $where_clause .= ' LIMIT ' . $LIMIT[0] . ',' . $LIMIT[1];
         }
       
       }
     
     }else{

      //不是数组且不为空直接拼接
       if( $where != null ){
         $where_clause .= ' '.$where;
       }
     }

     //返回组成的sql where 字符串
     return $where_clause;
   }


  /**
   *
   * column_push
   * 字段处理
   * 
   **/
   public function column_push( $columns ){

     //如果字段为*
     if( $columns == '*'){
       return $columns;
     }

     //如果是字符串
     if( is_string( $columns ) ){
       //转数组
       $columns = array( $columns );
     }

     //存储堆
     $stack = array();

     foreach( $columns as $key => $value ){

       //匹配valuer 
      preg_match( '/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i' , $value , $macth  );

       if( isset( $macth[ 1 ] , $macth[ 2 ] ) ){
         //如果是string1(string2)
         //转变成 "string1" AS "string2" 
         array_push( $stack , $this->column_quote( $macth[ 1 ] ).' AS '.$this->column_quote( $macth[ 2 ] ) );
       }else{
         //如果是string1
         array_push( $stack, $this->column_quote( $value ) );
       }
     }

     //将数组中的值用，分割输出成字符串
     return implode( $stack, ',' );
   }

  /**
   *
   * data_implode
   * 维数组的值转化为字符串
   *
   * where 安全过滤来保证
   *
   * @param array   $data             需要转化为字符串的数组
   * @param string  $connector        连接字符
   * @param string  $outer_connector  外部连接字符
   *
   **/

   protected function data_implode( $data, $connector, $outer_connector = null ){

     $wheres = array();

     if( is_array( $data ))
     {

       foreach( $data as $key => $value )
       {

         $type = gettype( $value );

         //匹配 AND OR 
         //并且是数组
         if(
           preg_match( '/^(AND|OR)(\s+#.*)$/i' , $key , $relation_match ) && 
           $type == 'array'
         )
         {

           $wheres[] = 0 !== count( array_diff_key( $value, array_keys( $value ) )) ?
             '('.$this->data_implode( $value, ' ', $relation_match[ 1 ]) . ')':
             '('.$this->inner_connector( $value, ' ', $relation_match[ 1 ], $connector ) . ')';
         }
         else
         {

           //匹配以#开头
           //匹配包括 > , < , <= , => , ! , <> 等操作符号
           preg_match( '/(#?)([\w\.\-]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\!?~)\])?/i' , $key , $macth );

           //过滤字符
           $column = $this->column_quote( $macth[ 2 ] );

           if( isset( $macth[ 4 ] ) )
           {
             //取得运算符
             $operator = $macth[ 4 ];

             //判断预算法是否等于 !
             if( $operator == '!' ){

               switch( $type ){

                 case 'NULL':
                   $wheres[] = $column.' IS NOT NULL';
                   break;

                 case 'array':
                   $wheres[] = $column . 'NOT IN (' . $this->array_quote( $value ) . ')';
                   break;

                 case 'integer':
                 case 'double' :
                   $wheres[] = $column . ' != '.$value;
                   break;
                 case 'boolean':
                   $wheres[] = $column . ' != '.( $value ? '1' : '0' );
                   break;
                 case 'string' :
                   $wheres[] = $column . ' != '.$this->fn_quote( $key, $value );
                   break;
               }
             }

             //判断预算法是否等于 <> 或者 ><
             if( $operator == '<>' || $operator == '><' )
             {

               if( $type == 'array' ){

                 if( $operator == '><' ){
                   $column .= ' NOT';
                 }

                 if( is_numeric( $value[ 0 ] ) && is_numeric( $value[ 1 ] )  ){

                   $wheres[] = '('.$column.' BETWEEN '.$value[ 0 ].' AND '.$value[ 1 ].')';
                 }else{

                   $wheres[] = '('.$column.' BETWEEN '.$this->quote( $value[ 0 ] ).' AND '.$this->quote( $value[ 1 ] ).')';
                 }
               }
             }


             //判断预算法是否等于 ~ 或者 !~
             if( $operator == '~' || $operator == '!~' )
             {
               if( $type == 'string' )
               {
                 $value = array( $value );
               }

               if( !empty( $value ))
               {

                 $like_clauses = array();

                 foreach( $value as $item )
                 {

                   if( preg_match( '/^(?!%).+(?<!%)$/' , $item ) )
                   {
                      // %vale% 
                     $item = '%'.$item.'%';
                   }

                   $like_clauses[] = $column . ( $operator === '!~' ? ' NOT':'' ) . ' LIKE '.$this->fn_quote( $key , $item );
                 }

                 $wheres[] = implode( ' OR',$like_clauses );
               }
             }

             //< > <= >= 存在数组之中
             if( in_array( $operator , array( '>', '<', '>=', '<=' ) ) )
             {

               if( is_numeric( $value )){//$value 是数值

                 $wheres[] = $column . ' ' .$operator . ' ' .$value;
               }elseif( strpos( $key , '#' ) ){//$key 中 包含有 #

                 $wheres[] = $column . ' ' .$operator . ' '.$this->fn_quote( $key ,$value );
               }else{

                 $wheres[] = $column . ' ' .$operator . ' '.$this->quote( $value );
               }
             }
           //end if match[ 4 ]
           //如果没有匹配到有操作符
           }else{
               switch( $type ){
                 case 'NULL'   :     //如果type 为 NULL
                   $wheres[] = $column. ' IS NULL '; 
                   break;
                 case 'array'  :    //如果type 为 array
                   $wheres[] = $column. ' IN ('.$this->array_quote( $value ). ')';
                   break;
                 case 'integer':
                 case 'double':     //如果为数值
                   $wheres[] = $column. ' = '.$value;
                   break;
                 case 'boolean':
                   $wheres[] = $column. ' = '.( $value ? '1' : '0' ); 
                   break;
                 case 'string' :    //字符串
                   $wheres[] = $column. ' = '.$this->fn_quote( $key, $value );
                   break;
               }
             }

         }//end else
       }//end foreach
     }//end if array
     return implode( $connector.' ', $wheres );
   }//end data_implode
  /**
   *
   * select 
   *
   * 查询数据表
   *
   * @param   $table    查询的表名
   * @param   $join   
   * @param   $columns  查询的行数  default null
   * @param   $where    查询的条件  default null
   *
   **/


  /**
   *
   * select_context
   *
   * 查询语句组合
   *
   * @param   $table    string    查询的表名
   * @param   $columns  str|arr   查询的字段  default null
   * @param   $where    str|arr   查询的条件  default null
   * @param   $join     str|arr   关联字段
   * @param   $fn       string    SQL使用的fn default null
   *
   **/

  public function select_context( $table, $column = null, $where = null, $join = null, $column_fn = null ){

    // table name
    $table      = '"'. $this->prefix . $table .'"';

    // 获取关联查询的联接 keys
    $join_keys  = is_array( $join ) ? array_keys( $join ) : null;

    var_dump(  $join_keys );

    if( isset( $join_keys[ 0 ] ) && strpos( $join_keys[ 0 ] , ' [ ' ) === 0 ){
    
      $table_join = array();

      //定义一组联接表示符
      $join_array = array(
        '>' => 'LEFT',
        '<' => 'RIGHT',
        '<>'=> 'FULL',
        '><'=> 'INNER'  //包含在内
      );

      foreach( $join as $key => $value ){

        //匹配key是否含 [<|>|<>|><]string(string)
        preg_match( '/(\[(\<|\>|\>\<|\<\>)\]?([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $key , $match );

        //如果两个string不等于空
        if( $match[ 2 ] != '' && $match[ 3 ] != '' ){


          //如果value 是 string 
          //拼接value
          if( is_string( $value )){
            $value = 'USING ("' . $value . '")';
          }

          //如果value是array
          if( is_array( $value )){

            if( isset( $value[ 0 ]) ){

              //以,拼接 $value 
              $value = 'USING ("' . implode( $value , '", "' ) . '")';
            }else{

              $joins = array();
              //
              foreach( $value as $keys => $values ){
                $joins[] = (
                  strpos( $keys , '.') > 0 ?
                    '"'.str_replace( '.' , '"."' , $keys) . '"':
                    $table . '."' . $keys . '"'
                    
                  ). ' = '.
                  '"'.( isset( $match[ 5 ]) ? $match[ 5 ] : $match[ 3 ] ).'"."'.$values.'"';
              }

              $value = ' ON '. implode( $joins, ' AND' );
            }
          }
          
          $table_join[] = $join_array[ $match[ 2 ] ]. ' JOIN "'.$macth[ 3 ].'" '.(isset( $macth[ 5 ]) ? 'AS "'.$macth[ 5 ] . '" ' : '' ).$value;
      }
    }
  

    $table .= ' '. implode( $table_join,' ');

    var_dump( $table );

  
    }
    if( ( isset( $column_fn ) ) ){

      if( $column_fn == 1 ){
      
        $column = '1';
      }else{

        $column = $column_fn . '(' . $this->column_push( $column ) . ')';
      }
    }else{

      //将column传到 column_push 方法中进行拼接
      $column = $this->column_push( $column );
    }

    return ' SELECT ' . $column . ' FROM '.$table . $this->where_clause( $where );
  }

  /**
   *
   * get()
   *
   * 从表中查询出一条数据
   *
   * @param   $table    查询的表名
   * @param   $join     联接
   * @param   $columns  查询的字段
   * @param   $where    查询的条件
   *
   * 查询的字段可以是一个或多个 多个必须要使用array 
   *
   **/
  public function get( $table, $join = null , $columns = null , $where = null ){

    $query = $this->query( $this->select_context( $table, $join, $column, $where ) . 'LIMIT 1' );

    var_dump( $query );
  
  
  }


  /**
   *
   * _debug
   *
   * 判断是否开启了debug
   *
   * @param $sql string 传入需要打印的sql语句
   * return false
   *
   **/
  private function _debug( $sql ){

    //如果开启了debug
    if( $this->debug_mode ){

      //输出sql语句
      var_dump( $sql );
      //关闭调试
      $this->debug_mode = false;
      return false;
    }
  
  }

  /**
   *
   * _logs
   *
   * 记录执行的sql到logs
   *
   * @param $sql string 传入记录到logs 的sql语句
   * return none;
   *
   **/
  private function _logs( $sql ){

    //保存在logs 数组
    array_push( $this->logs,$sql );
  
  }

  //log
  /**
   *
   * log
   * 输出logs 数组信息
   *
   * return array 返回sql 执行信息
   *
   **/
  public function log(){

    return $this->logs;

  }

  /**
   *
   * last_query
   * 返回最后一条执行的语句
   *
   * return string 返回最后一条执行的sql语句
   **/
  public function last_query(){

    //输出log中最后一条记录
    return end( $this->logs );
  }

  //error
  public function error(){

    $error_msg = array(
      'SQLSTATE 错误码:',
      '具体驱动错误码:',
      '具体驱动错误信息:'
    );

    echo 'SQL执行错误<br>';

    //errorInfo获取跟上一次语句句柄操作相关的扩展错误信息
    foreach( $this->pdo->errorInfo() as $key => $value ){
      echo $error_msg[ $key ],$value.'<br>';
    }

    //输出错误的代码
    echo '出错的sql语句:',$this->last_query();

  }


  //database info

  /**
   *
   * info
   *
   * 返回数据库相关信息
   *
   *
   **/

  public function info(){

    //输出array中定义的相关信息
    $output = array(
      'server'    =>  'SERVER_INFO',
      'dirver'    =>  'DRIVER_NAME',
      'client'    =>  'CLIENT_VERSION',
      'connection'=>  'CONNECTION_STATUS'
    );

    foreach( $output as $key => $value ){

      //使用pdo 的 getattribute 获取数据库的相关属性
      //constant 函数返回常量的值
      $output[ $key ] = $this->pdo->getAttribute( constant("PDO::ATTR_$value") );
    }

    return $output;

  } 

}

