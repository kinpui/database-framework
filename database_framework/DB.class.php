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
}

