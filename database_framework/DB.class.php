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

  /**
   *
   * select_context
   *
   **/

  /**
   *
   * insert
   * 插入记录
   *
   * @param $table  string  表名
   * @param $data   array   插入到表里的信息数组
   *
   * return 插入的最后一条信息记录
   *
   **/
  public function insert( $table,$datas ){

    //插入的数据返回结果
    $lastid = array();

    //确认信息数组是array
    if( is_array( $datas ) ){
      
      foreach( $datas as $key => $value ){
      
        echo $key,'===',$value,'<hr>';
          
        **********************************
      
      }

    }

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
  protected function inner_conjunct( $data,$conjunctor,$outer_conjunctor ){

    $haystack = array();
    
    if( empty( $conjunctor ) ){
      $conjunctor = '';
    }

    if( is_array( $data )){
      foreach( $data as $value ){
        $haystack[] = '('. $this->data_implode( $value , $conjunctor ).')';
      }

      return implode( $outer_conjunctor . ' ' , $haystack );
    }
  
  }


  /**
   *? end
   **/
  protected function fn_quote( $column,$string ){

    return ( strpos( $column , '#' ) == 0 && preg_match( '/^[A-Z0-9\_]*\([^)]*\)$/',$string ) )?
      $string:
      $this->quote( $string );

  }

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
   * 查询数据表
   *
   * @param   $table    查询的表名
   * @param   $join   
   * @param   $columns  查询的行数  default null
   * @param   $where    查询的条件  default null
   * @param   $fn       查询的条件  default null
   *
   **/

  protected function select_context( $table,$join,$columns = null,$where = null,$column_fn ){

    $table  = $this->prefix == ''?'"'.$table.'"':'"'. $this->prefix .$table .'"';


  
  }


  //public function


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

