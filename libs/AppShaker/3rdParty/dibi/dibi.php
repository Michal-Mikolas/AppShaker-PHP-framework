<?php //netteloader=IDataSource,IDibiVariable,IDibiProfiler,IDibiDriver,IDibiResultDriver,IDibiReflector,DibiDateTime,DibiObject,DibiHashMapBase,DibiHashMap,DibiException,DibiDriverException,DibiPcreException,DibiConnection,DibiResult,DibiResultIterator,DibiRow,DibiTranslator,DibiDataSource,DibiFluent,DibiDatabaseInfo,DibiTableInfo,DibiResultInfo,DibiColumnInfo,DibiForeignKeyInfo,DibiIndexInfo,DibiProfiler,DibiVariable,dibi,DibiMySqlReflector,DibiMySqlDriver,DibiMySqliDriver,DibiOdbcDriver,DibiSqliteReflector,DibiPdoDriver,DibiPostgreDriver,DibiSqliteDriver,DibiSqlite3Driver

/**
 * dibi - smart database abstraction layer (http://dibiphp.com)
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 *
 * @package    dibi
 */

if(version_compare(PHP_VERSION,'5.2.0','<')){throw
new
Exception('dibi needs PHP 5.2.0 or newer.');}@set_magic_quotes_runtime(FALSE);if(interface_exists('Nette\\IDebugPanel')){class_alias('Nette\\IDebugPanel','IDebugPanel');}elseif(!interface_exists('IDebugPanel')){interface
IDebugPanel{}}interface
IDataSource
extends
Countable,IteratorAggregate{}interface
IDibiVariable{function
toSql();}interface
IDibiProfiler{const
CONNECT=1,SELECT=4,INSERT=8,DELETE=16,UPDATE=32,QUERY=60,BEGIN=64,COMMIT=128,ROLLBACK=256,TRANSACTION=448,EXCEPTION=512,ALL=1023;function
before(DibiConnection$connection,$event,$sql=NULL);function
after($ticket,$result=NULL);function
exception(DibiDriverException$exception);}interface
IDibiDriver{function
connect(array&$config);function
disconnect();function
query($sql);function
getAffectedRows();function
getInsertId($sequence);function
begin($savepoint=NULL);function
commit($savepoint=NULL);function
rollback($savepoint=NULL);function
getResource();function
getReflector();function
escape($value,$type);function
escapeLike($value,$pos);function
applyLimit(&$sql,$limit,$offset);}interface
IDibiResultDriver{function
getRowCount();function
seek($row);function
fetch($type);function
free();function
getResultColumns();function
getResultResource();function
unescape($value,$type);}interface
IDibiReflector{function
getTables();function
getColumns($table);function
getIndexes($table);function
getForeignKeys($table);}class
DibiDateTime
extends
DateTime{function
__construct($time='now',DateTimeZone$timezone=NULL){if(is_numeric($time)){$time=date('Y-m-d H:i:s',$time);}if($timezone===NULL){parent::__construct($time);}else{parent::__construct($time,$timezone);}}function
__sleep(){$this->fix=array($this->format('Y-m-d H:i:s'),$this->getTimezone()->getName());return
array('fix');}function
__wakeup(){$this->__construct($this->fix[0],new
DateTimeZone($this->fix[1]));unset($this->fix);}function
getTimestamp(){return(int)$this->format('U');}function
setTimestamp($timestamp){return$this->__construct(date('Y-m-d H:i:s',$timestamp),new
DateTimeZone($this->getTimezone()->getName()));}}abstract
class
DibiObject{private
static$extMethods;final
function
getClass(){return
get_class($this);}final
function
getReflection(){return
new
ReflectionObject($this);}function
__call($name,$args){$class=get_class($this);if($name===''){throw
new
MemberAccessException("Call to class '$class' method without name.");}if(preg_match('#^on[A-Z]#',$name)){$rp=new
ReflectionProperty($class,$name);if($rp->isPublic()&&!$rp->isStatic()){$list=$this->$name;if(is_array($list)||$list
instanceof
Traversable){foreach($list
as$handler){if(is_object($handler)){call_user_func_array(array($handler,'__invoke'),$args);}else{call_user_func_array($handler,$args);}}}return
NULL;}}if($cb=self::extensionMethod("$class::$name")){array_unshift($args,$this);return
call_user_func_array($cb,$args);}throw
new
MemberAccessException("Call to undefined method $class::$name().");}static
function
__callStatic($name,$args){$class=get_called_class();throw
new
MemberAccessException("Call to undefined static method $class::$name().");}static
function
extensionMethod($name,$callback=NULL){if(self::$extMethods===NULL||$name===NULL){$list=get_defined_functions();foreach($list['user']as$fce){$pair=explode('_prototype_',$fce);if(count($pair)===2){self::$extMethods[$pair[1]][$pair[0]]=$fce;self::$extMethods[$pair[1]]['']=NULL;}}if($name===NULL)return
NULL;}$name=strtolower($name);$a=strrpos($name,':');if($a===FALSE){$class=strtolower(get_called_class());$l=&self::$extMethods[$name];}else{$class=substr($name,0,$a-1);$l=&self::$extMethods[substr($name,$a+1)];}if($callback!==NULL){$l[$class]=$callback;$l['']=NULL;return
NULL;}if(empty($l)){return
FALSE;}elseif(isset($l[''][$class])){return$l[''][$class];}$cl=$class;do{$cl=strtolower($cl);if(isset($l[$cl])){return$l[''][$class]=$l[$cl];}}while(($cl=get_parent_class($cl))!==FALSE);foreach(class_implements($class)as$cl){$cl=strtolower($cl);if(isset($l[$cl])){return$l[''][$class]=$l[$cl];}}return$l[''][$class]=FALSE;}function&__get($name){$class=get_class($this);if($name===''){throw
new
MemberAccessException("Cannot read a class '$class' property without name.");}$name[0]=$name[0]&"\xDF";$m='get'.$name;if(self::hasAccessor($class,$m)){$val=$this->$m();return$val;}$m='is'.$name;if(self::hasAccessor($class,$m)){$val=$this->$m();return$val;}$name=func_get_arg(0);throw
new
MemberAccessException("Cannot read an undeclared property $class::\$$name.");}function
__set($name,$value){$class=get_class($this);if($name===''){throw
new
MemberAccessException("Cannot assign to a class '$class' property without name.");}$name[0]=$name[0]&"\xDF";if(self::hasAccessor($class,'get'.$name)||self::hasAccessor($class,'is'.$name)){$m='set'.$name;if(self::hasAccessor($class,$m)){$this->$m($value);return;}else{$name=func_get_arg(0);throw
new
MemberAccessException("Cannot assign to a read-only property $class::\$$name.");}}$name=func_get_arg(0);throw
new
MemberAccessException("Cannot assign to an undeclared property $class::\$$name.");}function
__isset($name){$name[0]=$name[0]&"\xDF";return$name!==''&&self::hasAccessor(get_class($this),'get'.$name);}function
__unset($name){$class=get_class($this);throw
new
MemberAccessException("Cannot unset the property $class::\$$name.");}private
static
function
hasAccessor($c,$m){static$cache;if(!isset($cache[$c])){$cache[$c]=array_flip(get_class_methods($c));}return
isset($cache[$c][$m]);}}abstract
class
DibiHashMapBase{private$callback;function
__construct($callback){$this->setCallback($callback);}function
setCallback($callback){if(!is_callable($callback)){$able=is_callable($callback,TRUE,$textual);throw
new
InvalidArgumentException("Handler '$textual' is not ".($able?'callable.':'valid PHP callback.'));}$this->callback=$callback;}function
getCallback(){return$this->callback;}}final
class
DibiHashMap
extends
DibiHashMapBase{function
__set($nm,$val){if($nm==''){$nm="\xFF";}$this->$nm=$val;}function
__get($nm){if($nm==''){$nm="\xFF";return
isset($this->$nm)?$this->$nm:$this->$nm=call_user_func($this->getCallback(),'');}else{return$this->$nm=call_user_func($this->getCallback(),$nm);}}}if(!defined('NETTE')){class
NotImplementedException
extends
LogicException{}class
NotSupportedException
extends
LogicException{}class
MemberAccessException
extends
LogicException{}class
InvalidStateException
extends
RuntimeException{}class
IOException
extends
RuntimeException{}class
FileNotFoundException
extends
IOException{}}class
DibiException
extends
Exception
implements
IDebugPanel{private$sql;function
__construct($message=NULL,$code=0,$sql=NULL){parent::__construct($message,(int)$code);$this->sql=$sql;}final
function
getSql(){return$this->sql;}function
__toString(){return
parent::__toString().($this->sql?"\nSQL: ".$this->sql:'');}function
getTab(){return'SQL';}function
getPanel(){return$this->sql?dibi::dump($this->sql,TRUE):NULL;}function
getId(){return
__CLASS__;}}class
DibiDriverException
extends
DibiException{private
static$errorMsg;static
function
tryError(){set_error_handler(array(__CLASS__,'_errorHandler'),E_ALL);self::$errorMsg=NULL;}static
function
catchError(&$message){restore_error_handler();$message=self::$errorMsg;self::$errorMsg=NULL;return$message!==NULL;}static
function
_errorHandler($code,$message){restore_error_handler();if(ini_get('html_errors')){$message=strip_tags($message);$message=html_entity_decode($message);}self::$errorMsg=$message;}}class
DibiPcreException
extends
Exception{function
__construct($message='%msg.'){static$messages=array(PREG_INTERNAL_ERROR=>'Internal error',PREG_BACKTRACK_LIMIT_ERROR=>'Backtrack limit was exhausted',PREG_RECURSION_LIMIT_ERROR=>'Recursion limit was exhausted',PREG_BAD_UTF8_ERROR=>'Malformed UTF-8 data',5=>'Offset didn\'t correspond to the begin of a valid UTF-8 code point');$code=preg_last_error();parent::__construct(str_replace('%msg',isset($messages[$code])?$messages[$code]:'Unknown error',$message),$code);}}class
DibiConnection
extends
DibiObject{private$config;private$driver;private$translator;private$profiler;private$connected=FALSE;private$substitutes;function
__construct($config,$name=NULL){if(is_string($config)){parse_str($config,$config);}elseif($config
instanceof
Traversable){$tmp=array();foreach($config
as$key=>$val){$tmp[$key]=$val
instanceof
Traversable?iterator_to_array($val):$val;}$config=$tmp;}elseif(!is_array($config)){throw
new
InvalidArgumentException('Configuration must be array, string or object.');}self::alias($config,'username','user');self::alias($config,'password','pass');self::alias($config,'host','hostname');self::alias($config,'result|detectTypes','resultDetectTypes');self::alias($config,'result|formatDateTime','resultDateTime');if(!isset($config['driver'])){$config['driver']=dibi::$defaultDriver;}$driver=preg_replace('#[^a-z0-9_]#','_',strtolower($config['driver']));$class="Dibi".$driver."Driver";if(!class_exists($class,FALSE)){ include_once dirname(__FILE__)."/../drivers/$driver.php";if(!class_exists($class,FALSE)){throw
new
DibiException("Unable to create instance of dibi driver '$class'.");}}$config['name']=$name;$this->config=$config;$this->driver=new$class;$this->translator=new
DibiTranslator($this);$profilerCfg=&$config['profiler'];if(is_scalar($profilerCfg)){$profilerCfg=array('run'=>(bool)$profilerCfg,'class'=>strlen($profilerCfg)>1?$profilerCfg:NULL);}if(!empty($profilerCfg['run'])){class_exists('dibi');$class=isset($profilerCfg['class'])?$profilerCfg['class']:'DibiProfiler';if(!class_exists($class)){throw
new
DibiException("Unable to create instance of dibi profiler '$class'.");}$this->setProfiler(new$class($profilerCfg));}$this->substitutes=new
DibiHashMap(create_function('$expr','return ":$expr:";'));if(!empty($config['substitutes'])){foreach($config['substitutes']as$key=>$value){$this->substitutes->$key=$value;}}if(empty($config['lazy'])){$this->connect();}}function
__destruct(){$this->connected&&$this->disconnect();}final
function
connect(){if($this->profiler!==NULL){$ticket=$this->profiler->before($this,IDibiProfiler::CONNECT);}$this->driver->connect($this->config);$this->connected=TRUE;if(isset($ticket)){$this->profiler->after($ticket);}}final
function
disconnect(){$this->driver->disconnect();$this->connected=FALSE;}final
function
isConnected(){return$this->connected;}final
function
getConfig($key=NULL,$default=NULL){if($key===NULL){return$this->config;}elseif(isset($this->config[$key])){return$this->config[$key];}else{return$default;}}static
function
alias(&$config,$key,$alias){$foo=&$config;foreach(explode('|',$key)as$key)$foo=&$foo[$key];if(!isset($foo)&&isset($config[$alias])){$foo=$config[$alias];unset($config[$alias]);}}final
function
getDriver(){$this->connected||$this->connect();return$this->driver;}final
function
query($args){$args=func_get_args();return$this->nativeQuery($this->translateArgs($args));}final
function
translate($args){$args=func_get_args();return$this->translateArgs($args);}final
function
test($args){$args=func_get_args();try{dibi::dump($this->translateArgs($args));return
TRUE;}catch(DibiException$e){dibi::dump($e->getSql());return
FALSE;}}final
function
dataSource($args){$args=func_get_args();return
new
DibiDataSource($this->translateArgs($args),$this);}private
function
translateArgs($args){$this->connected||$this->connect();return$this->translator->translate($args);}final
function
nativeQuery($sql){$this->connected||$this->connect();if($this->profiler!==NULL){$event=IDibiProfiler::QUERY;if(preg_match('#\s*(SELECT|UPDATE|INSERT|DELETE)#iA',$sql,$matches)){static$events=array('SELECT'=>IDibiProfiler::SELECT,'UPDATE'=>IDibiProfiler::UPDATE,'INSERT'=>IDibiProfiler::INSERT,'DELETE'=>IDibiProfiler::DELETE);$event=$events[strtoupper($matches[1])];}$ticket=$this->profiler->before($this,$event,$sql);}dibi::$sql=$sql;if($res=$this->driver->query($sql)){$res=$this->createResultSet($res);}else{$res=$this->driver->getAffectedRows();}if(isset($ticket)){$this->profiler->after($ticket,$res);}return$res;}function
getAffectedRows(){$this->connected||$this->connect();$rows=$this->driver->getAffectedRows();if(!is_int($rows)||$rows<0)throw
new
DibiException('Cannot retrieve number of affected rows.');return$rows;}function
affectedRows(){return$this->getAffectedRows();}function
getInsertId($sequence=NULL){$this->connected||$this->connect();$id=$this->driver->getInsertId($sequence);if($id<1)throw
new
DibiException('Cannot retrieve last generated ID.');return(int)$id;}function
insertId($sequence=NULL){return$this->getInsertId($sequence);}function
begin($savepoint=NULL){$this->connected||$this->connect();if($this->profiler!==NULL){$ticket=$this->profiler->before($this,IDibiProfiler::BEGIN,$savepoint);}$this->driver->begin($savepoint);if(isset($ticket)){$this->profiler->after($ticket);}}function
commit($savepoint=NULL){$this->connected||$this->connect();if($this->profiler!==NULL){$ticket=$this->profiler->before($this,IDibiProfiler::COMMIT,$savepoint);}$this->driver->commit($savepoint);if(isset($ticket)){$this->profiler->after($ticket);}}function
rollback($savepoint=NULL){$this->connected||$this->connect();if($this->profiler!==NULL){$ticket=$this->profiler->before($this,IDibiProfiler::ROLLBACK,$savepoint);}$this->driver->rollback($savepoint);if(isset($ticket)){$this->profiler->after($ticket);}}function
createResultSet(IDibiResultDriver$resultDriver){return
new
DibiResult($resultDriver,$this->config['result']);}function
command(){return
new
DibiFluent($this);}function
select($args){$args=func_get_args();return$this->command()->__call('select',$args);}function
update($table,$args){if(!(is_array($args)||$args
instanceof
Traversable)){throw
new
InvalidArgumentException('Arguments must be array or Traversable.');}return$this->command()->update('%n',$table)->set($args);}function
insert($table,$args){if($args
instanceof
Traversable){$args=iterator_to_array($args);}elseif(!is_array($args)){throw
new
InvalidArgumentException('Arguments must be array or Traversable.');}return$this->command()->insert()->into('%n',$table,'(%n)',array_keys($args))->values('%l',$args);}function
delete($table){return$this->command()->delete()->from('%n',$table);}function
setProfiler(IDibiProfiler$profiler=NULL){$this->profiler=$profiler;return$this;}function
getProfiler(){return$this->profiler;}function
getSubstitutes(){return$this->substitutes;}function
substitute($value){return
strpos($value,':')===FALSE?$value:preg_replace_callback('#:([^:\s]*):#',array($this,'subCb'),$value);}private
function
subCb($m){return$this->substitutes->{$m[1]};}function
fetch($args){$args=func_get_args();return$this->query($args)->fetch();}function
fetchAll($args){$args=func_get_args();return$this->query($args)->fetchAll();}function
fetchSingle($args){$args=func_get_args();return$this->query($args)->fetchSingle();}function
fetchPairs($args){$args=func_get_args();return$this->query($args)->fetchPairs();}function
loadFile($file){$this->connected||$this->connect();@set_time_limit(0);$handle=@fopen($file,'r');if(!$handle){throw
new
FileNotFoundException("Cannot open file '$file'.");}$count=0;$sql='';while(!feof($handle)){$s=fgets($handle);$sql.=$s;if(substr(rtrim($s),-1)===';'){$this->driver->query($sql);$sql='';$count++;}}fclose($handle);return$count;}function
getDatabaseInfo(){$this->connected||$this->connect();return
new
DibiDatabaseInfo($this->driver->getReflector(),isset($this->config['database'])?$this->config['database']:NULL);}function
__wakeup(){throw
new
NotSupportedException('You cannot serialize or unserialize '.$this->getClass().' instances.');}function
__sleep(){throw
new
NotSupportedException('You cannot serialize or unserialize '.$this->getClass().' instances.');}}class
DibiResult
extends
DibiObject
implements
IDataSource{private$driver;private$types;private$meta;private$fetched=FALSE;private$rowClass='DibiRow';private$dateFormat='';function
__construct($driver,$config){$this->driver=$driver;if(!empty($config['detectTypes'])){$this->detectTypes();}if(!empty($config['formatDateTime'])){$this->dateFormat=is_string($config['formatDateTime'])?$config['formatDateTime']:'';}}final
function
getResource(){return$this->getDriver()->getResultResource();}final
function
free(){if($this->driver!==NULL){$this->driver->free();$this->driver=$this->meta=NULL;}}private
function
getDriver(){if($this->driver===NULL){throw
new
InvalidStateException('Result-set was released from memory.');}return$this->driver;}final
function
seek($row){return($row!==0||$this->fetched)?(bool)$this->getDriver()->seek($row):TRUE;}final
function
count(){return$this->getDriver()->getRowCount();}final
function
getRowCount(){return$this->getDriver()->getRowCount();}final
function
rowCount(){trigger_error(__METHOD__.'() is deprecated; use count($res) or $res->getRowCount() instead.',E_USER_WARNING);return$this->getDriver()->getRowCount();}final
function
getIterator(){if(func_num_args()){trigger_error(__METHOD__.' arguments $offset & $limit have been dropped; use SQL clauses instead.',E_USER_WARNING);}return
new
DibiResultIterator($this);}function
setRowClass($class){$this->rowClass=$class;return$this;}function
getRowClass(){return$this->rowClass;}final
function
fetch(){$row=$this->getDriver()->fetch(TRUE);if(!is_array($row))return
FALSE;$this->fetched=TRUE;if($this->types!==NULL){foreach($this->types
as$col=>$type){if(isset($row[$col])){$row[$col]=$this->convert($row[$col],$type);}}}return
new$this->rowClass($row);}final
function
fetchSingle(){$row=$this->getDriver()->fetch(TRUE);if(!is_array($row))return
FALSE;$this->fetched=TRUE;$value=reset($row);$key=key($row);if(isset($this->types[$key])){return$this->convert($value,$this->types[$key]);}return$value;}final
function
fetchAll($offset=NULL,$limit=NULL){$limit=$limit===NULL?-1:(int)$limit;$this->seek((int)$offset);$row=$this->fetch();if(!$row)return
array();$data=array();do{if($limit===0)break;$limit--;$data[]=$row;}while($row=$this->fetch());return$data;}final
function
fetchAssoc($assoc){if(strpos($assoc,',')!==FALSE){return$this->oldFetchAssoc($assoc);}$this->seek(0);$row=$this->fetch();if(!$row)return
array();$data=NULL;$assoc=preg_split('#(\[\]|->|=|\|)#',$assoc,NULL,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);foreach($assoc
as$as){if($as!=='[]'&&$as!=='='&&$as!=='->'&&$as!=='|'&&!property_exists($row,$as)){throw
new
InvalidArgumentException("Unknown column '$as' in associative descriptor.");}}if($as==='->'){array_pop($assoc);}if(empty($assoc)){$assoc[]='[]';}do{$x=&$data;foreach($assoc
as$i=>$as){if($as==='[]'){$x=&$x[];}elseif($as==='='){$x=$row->{$assoc[$i+1]};continue
2;}elseif($as==='->'){if($x===NULL){$x=clone$row;$x=&$x->{$assoc[$i+1]};$x=NULL;}else{$x=&$x->{$assoc[$i+1]};}}elseif($as!=='|'){$x=&$x[$row->$as];}}if($x===NULL){$x=$row;}}while($row=$this->fetch());unset($x);return$data;}private
function
oldFetchAssoc($assoc){$this->seek(0);$row=$this->fetch();if(!$row)return
array();$data=NULL;$assoc=explode(',',$assoc);$leaf='@';$last=count($assoc)-1;while($assoc[$last]==='='||$assoc[$last]==='@'){$leaf=$assoc[$last];unset($assoc[$last]);$last--;if($last<0){$assoc[]='#';break;}}do{$x=&$data;foreach($assoc
as$i=>$as){if($as==='#'){$x=&$x[];}elseif($as==='='){if($x===NULL){$x=$row->toArray();$x=&$x[$assoc[$i+1]];$x=NULL;}else{$x=&$x[$assoc[$i+1]];}}elseif($as==='@'){if($x===NULL){$x=clone$row;$x=&$x->{$assoc[$i+1]};$x=NULL;}else{$x=&$x->{$assoc[$i+1]};}}else{$x=&$x[$row->$as];}}if($x===NULL){if($leaf==='='){$x=$row->toArray();}else{$x=$row;}}}while($row=$this->fetch());unset($x);return$data;}final
function
fetchPairs($key=NULL,$value=NULL){$this->seek(0);$row=$this->fetch();if(!$row)return
array();$data=array();if($value===NULL){if($key!==NULL){throw
new
InvalidArgumentException("Either none or both columns must be specified.");}$tmp=array_keys($row->toArray());$key=$tmp[0];if(count($row)<2){do{$data[]=$row[$key];}while($row=$this->fetch());return$data;}$value=$tmp[1];}else{if(!property_exists($row,$value)){throw
new
InvalidArgumentException("Unknown value column '$value'.");}if($key===NULL){do{$data[]=$row[$value];}while($row=$this->fetch());return$data;}if(!property_exists($row,$key)){throw
new
InvalidArgumentException("Unknown key column '$key'.");}}do{$data[$row[$key]]=$row[$value];}while($row=$this->fetch());return$data;}final
function
setType($col,$type){$this->types[$col]=$type;return$this;}final
function
detectTypes(){foreach($this->getInfo()->getColumns()as$col){$this->types[$col->getName()]=$col->getType();}}final
function
setTypes(array$types){$this->types=$types;return$this;}final
function
getType($col){return
isset($this->types[$col])?$this->types[$col]:NULL;}protected
function
convert($value,$type){if($value===NULL||$value===FALSE){return
NULL;}switch($type){case
dibi::TEXT:return(string)$value;case
dibi::BINARY:return$this->getDriver()->unescape($value,$type);case
dibi::INTEGER:return
is_float($tmp=$value*1)?$value:$tmp;case
dibi::FLOAT:return(float)$value;case
dibi::DATE:case
dibi::DATETIME:if((int)$value===0){return
NULL;}elseif($this->dateFormat===''){return
new
DibiDateTime(is_numeric($value)?date('Y-m-d H:i:s',$value):$value);}elseif($this->dateFormat==='U'){return
is_numeric($value)?(int)$value:strtotime($value);}elseif(is_numeric($value)){return
date($this->dateFormat,$value);}else{$value=new
DibiDateTime($value);return$value->format($this->dateFormat);}case
dibi::BOOL:return((bool)$value)&&$value!=='f'&&$value!=='F';default:return$value;}}function
getInfo(){if($this->meta===NULL){$this->meta=new
DibiResultInfo($this->getDriver());}return$this->meta;}final
function
getColumns(){return$this->getInfo()->getColumns();}function
getColumnNames($fullNames=FALSE){trigger_error(__METHOD__.'() is deprecated; use $res->getInfo()->getColumnNames() instead.',E_USER_WARNING);return$this->getInfo()->getColumnNames($fullNames);}final
function
dump(){$i=0;$this->seek(0);while($row=$this->fetch()){if($i===0){echo"\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";foreach($row
as$col=>$foo){echo"\t\t<th>".htmlSpecialChars($col)."</th>\n";}echo"\t</tr>\n</thead>\n<tbody>\n";}echo"\t<tr>\n\t\t<th>",$i,"</th>\n";foreach($row
as$col){echo"\t\t<td>",htmlSpecialChars($col),"</td>\n";}echo"\t</tr>\n";$i++;}if($i===0){echo'<p><em>empty result set</em></p>';}else{echo"</tbody>\n</table>\n";}}}class
DibiResultIterator
implements
Iterator,Countable{private$result;private$row;private$pointer;function
__construct(DibiResult$result){$this->result=$result;}function
rewind(){$this->pointer=0;$this->result->seek(0);$this->row=$this->result->fetch();}function
key(){return$this->pointer;}function
current(){return$this->row;}function
next(){$this->row=$this->result->fetch();$this->pointer++;}function
valid(){return!empty($this->row);}function
count(){return$this->result->getRowCount();}}class
DibiRow
implements
ArrayAccess,IteratorAggregate,Countable{function
__construct($arr){foreach($arr
as$k=>$v)$this->$k=$v;}function
toArray(){return(array)$this;}function
asDateTime($key,$format=NULL){$time=$this[$key];if((int)$time===0){return
NULL;}$dt=new
DibiDateTime(is_numeric($time)?date('Y-m-d H:i:s',$time):$time);return$format===NULL?$dt:$dt->format($format);}function
asTimestamp($key){$time=$this[$key];return(int)$time===0?NULL:(is_numeric($time)?(int)$time:strtotime($time));}function
asBool($key){$value=$this[$key];if($value===NULL||$value===FALSE){return$value;}else{return((bool)$value)&&$value!=='f'&&$value!=='F';}}function
asDate($key,$format=NULL){if($format===NULL){return$this->asTimestamp($key);}else{return$this->asDateTime($key,$format===TRUE?NULL:$format);}}final
function
count(){return
count((array)$this);}final
function
getIterator(){return
new
ArrayIterator($this);}final
function
offsetSet($nm,$val){$this->$nm=$val;}final
function
offsetGet($nm){return$this->$nm;}final
function
offsetExists($nm){return
isset($this->$nm);}final
function
offsetUnset($nm){unset($this->$nm);}}final
class
DibiTranslator
extends
DibiObject{private$connection;private$driver;private$cursor;private$args;private$hasError;private$comment;private$ifLevel;private$ifLevelStart;private$limit;private$offset;private$identifiers;function
__construct(DibiConnection$connection){$this->connection=$connection;}function
translate(array$args){$this->identifiers=new
DibiHashMap(array($this,'delimite'));$this->driver=$this->connection->getDriver();$args=array_values($args);while(count($args)===1&&is_array($args[0])){$args=array_values($args[0]);}$this->args=$args;$this->limit=-1;$this->offset=0;$this->hasError=FALSE;$commandIns=NULL;$lastArr=NULL;$cursor=&$this->cursor;$cursor=0;$this->ifLevel=$this->ifLevelStart=0;$comment=&$this->comment;$comment=FALSE;$sql=array();while($cursor<count($this->args)){$arg=$this->args[$cursor];$cursor++;if(is_string($arg)){$toSkip=strcspn($arg,'`[\'":%');if(strlen($arg)===$toSkip){$sql[]=$arg;}else{$sql[]=substr($arg,0,$toSkip).preg_replace_callback('/(?=[`[\'":%?])(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|(\'|")|:(\S*?:)([a-zA-Z0-9._]?)|%([a-zA-Z~][a-zA-Z0-9~]{0,5})|(\?))/s',array($this,'cb'),substr($arg,$toSkip));if(preg_last_error())throw
new
DibiPcreException;}continue;}if($comment){$sql[]='...';continue;}if($arg
instanceof
Traversable){$arg=iterator_to_array($arg);}if(is_array($arg)){if(is_string(key($arg))){if($commandIns===NULL){$commandIns=strtoupper(substr(ltrim($this->args[0]),0,6));$commandIns=$commandIns==='INSERT'||$commandIns==='REPLAC';$sql[]=$this->formatValue($arg,$commandIns?'v':'a');}else{if($lastArr===$cursor-1)$sql[]=',';$sql[]=$this->formatValue($arg,$commandIns?'l':'a');}$lastArr=$cursor;continue;}}$sql[]=$this->formatValue($arg,FALSE);}if($comment)$sql[]="*/";$sql=implode(' ',$sql);if($this->hasError){throw
new
DibiException('SQL translate error',0,$sql);}if($this->limit>-1||$this->offset>0){$this->driver->applyLimit($sql,$this->limit,$this->offset);}return$sql;}function
formatValue($value,$modifier){if($this->comment){return"...";}if($value
instanceof
Traversable){$value=iterator_to_array($value);}if(is_array($value)){$vx=$kx=array();switch($modifier){case'and':case'or':if(empty($value)){return'1=1';}foreach($value
as$k=>$v){if(is_string($k)){$pair=explode('%',$k,2);$k=$this->identifiers->{$pair[0]}.' ';if(!isset($pair[1])){$v=$this->formatValue($v,FALSE);$vx[]=$k.($v==='NULL'?'IS ':'= ').$v;}elseif($pair[1]==='ex'){$vx[]=$k.$this->formatValue($v,'ex');}else{$v=$this->formatValue($v,$pair[1]);$vx[]=$k.($pair[1]==='l'||$pair[1]==='in'?'IN ':($v==='NULL'?'IS ':'= ')).$v;}}else{$vx[]=$this->formatValue($v,'ex');}}return'('.implode(') '.strtoupper($modifier).' (',$vx).')';case'n':foreach($value
as$k=>$v){if(is_string($k)){$vx[]=$this->identifiers->$k.(empty($v)?'':' AS '.$v);}else{$pair=explode('%',$v,2);$vx[]=$this->identifiers->{$pair[0]};}}return
implode(', ',$vx);case'a':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$vx[]=$this->identifiers->{$pair[0]}.'='.$this->formatValue($v,isset($pair[1])?$pair[1]:(is_array($v)?'ex':FALSE));}return
implode(', ',$vx);case'in':case'l':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$vx[]=$this->formatValue($v,isset($pair[1])?$pair[1]:(is_array($v)?'ex':FALSE));}return'('.(($vx||$modifier==='l')?implode(', ',$vx):'NULL').')';case'v':foreach($value
as$k=>$v){$pair=explode('%',$k,2);$kx[]=$this->identifiers->{$pair[0]};$vx[]=$this->formatValue($v,isset($pair[1])?$pair[1]:(is_array($v)?'ex':FALSE));}return'('.implode(', ',$kx).') VALUES ('.implode(', ',$vx).')';case'm':foreach($value
as$k=>$v){if(is_array($v)){if(isset($proto)){if($proto!==array_keys($v)){$this->hasError=TRUE;return'**Multi-insert array "'.$k.'" is different.**';}}else{$proto=array_keys($v);}}else{$this->hasError=TRUE;return'**Unexpected type '.gettype($v).'**';}$pair=explode('%',$k,2);$kx[]=$this->identifiers->{$pair[0]};foreach($v
as$k2=>$v2){$vx[$k2][]=$this->formatValue($v2,isset($pair[1])?$pair[1]:(is_array($v2)?'ex':FALSE));}}foreach($vx
as$k=>$v){$vx[$k]='('.implode(', ',$v).')';}return'('.implode(', ',$kx).') VALUES '.implode(', ',$vx);case'by':foreach($value
as$k=>$v){if(is_array($v)){$vx[]=$this->formatValue($v,'ex');}elseif(is_string($k)){$v=(is_string($v)&&strncasecmp($v,'d',1))||$v>0?'ASC':'DESC';$vx[]=$this->identifiers->$k.' '.$v;}else{$vx[]=$this->identifiers->$v;}}return
implode(', ',$vx);case'ex':case'sql':$translator=new
self($this->connection);return$translator->translate($value);default:foreach($value
as$v){$vx[]=$this->formatValue($v,$modifier);}return
implode(', ',$vx);}}if($modifier){if($value!==NULL&&!is_scalar($value)&&!($value
instanceof
DateTime)){$this->hasError=TRUE;return'**Unexpected type '.gettype($value).'**';}switch($modifier){case's':case'bin':case'b':return$value===NULL?'NULL':$this->driver->escape($value,$modifier);case'sN':case'sn':return$value==''?'NULL':$this->driver->escape($value,dibi::TEXT);case'iN':case'in':if($value=='')$value=NULL;case'i':case'u':if(is_string($value)&&preg_match('#[+-]?\d++(e\d+)?$#A',$value)){return$value;}else{return$value===NULL?'NULL':(string)(int)($value+0);}case'f':if(is_string($value)&&is_numeric($value)&&strpos($value,'x')===FALSE){return$value;}else{return$value===NULL?'NULL':rtrim(rtrim(number_format($value+0,5,'.',''),'0'),'.');}case'd':case't':if($value===NULL){return'NULL';}else{if(is_numeric($value)){$value=(int)$value;}elseif(is_string($value)){$value=new
DateTime($value);}return$this->driver->escape($value,$modifier);}case'by':case'n':return$this->identifiers->$value;case'ex':case'sql':$value=(string)$value;$toSkip=strcspn($value,'`[\'":');if(strlen($value)!==$toSkip){$value=substr($value,0,$toSkip).preg_replace_callback('/(?=[`[\'":])(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|(\'|")|:(\S*?:)([a-zA-Z0-9._]?))/s',array($this,'cb'),substr($value,$toSkip));if(preg_last_error())throw
new
DibiPcreException;}return$value;case'SQL':return(string)$value;case'like~':return$this->driver->escapeLike($value,1);case'~like':return$this->driver->escapeLike($value,-1);case'~like~':return$this->driver->escapeLike($value,0);case'and':case'or':case'a':case'l':case'v':$this->hasError=TRUE;return'**Unexpected type '.gettype($value).'**';default:$this->hasError=TRUE;return"**Unknown or invalid modifier %$modifier**";}}if(is_string($value)){return$this->driver->escape($value,dibi::TEXT);}elseif(is_int($value)){return(string)$value;}elseif(is_float($value)){return
rtrim(rtrim(number_format($value,5,'.',''),'0'),'.');}elseif(is_bool($value)){return$this->driver->escape($value,dibi::BOOL);}elseif($value===NULL){return'NULL';}elseif($value
instanceof
DateTime){return$this->driver->escape($value,dibi::DATETIME);}elseif($value
instanceof
IDibiVariable){return(string)$value->toSql();}else{$this->hasError=TRUE;return'**Unexpected '.gettype($value).'**';}}private
function
cb($matches){if(!empty($matches[11])){$cursor=&$this->cursor;if($cursor>=count($this->args)){$this->hasError=TRUE;return"**Extra placeholder**";}$cursor++;return$this->formatValue($this->args[$cursor-1],FALSE);}if(!empty($matches[10])){$mod=$matches[10];$cursor=&$this->cursor;if($cursor>=count($this->args)&&$mod!=='else'&&$mod!=='end'){$this->hasError=TRUE;return"**Extra modifier %$mod**";}if($mod==='if'){$this->ifLevel++;$cursor++;if(!$this->comment&&!$this->args[$cursor-1]){$this->ifLevelStart=$this->ifLevel;$this->comment=TRUE;return"/*";}return'';}elseif($mod==='else'){if($this->ifLevelStart===$this->ifLevel){$this->ifLevelStart=0;$this->comment=FALSE;return"*/";}elseif(!$this->comment){$this->ifLevelStart=$this->ifLevel;$this->comment=TRUE;return"/*";}}elseif($mod==='end'){$this->ifLevel--;if($this->ifLevelStart===$this->ifLevel+1){$this->ifLevelStart=0;$this->comment=FALSE;return"*/";}return'';}elseif($mod==='ex'){array_splice($this->args,$cursor,1,$this->args[$cursor]);return'';}elseif($mod==='lmt'){if($this->args[$cursor]!==NULL)$this->limit=(int)$this->args[$cursor];$cursor++;return'';}elseif($mod==='ofs'){if($this->args[$cursor]!==NULL)$this->offset=(int)$this->args[$cursor];$cursor++;return'';}else{$cursor++;return$this->formatValue($this->args[$cursor-1],$mod);}}if($this->comment)return'...';if($matches[1])return$this->identifiers->{$matches[1]};if($matches[2])return$this->identifiers->{$matches[2]};if($matches[3])return$this->driver->escape(str_replace("''","'",$matches[4]),dibi::TEXT);if($matches[5])return$this->driver->escape(str_replace('""','"',$matches[6]),dibi::TEXT);if($matches[7]){$this->hasError=TRUE;return'**Alone quote**';}if($matches[8]){$m=substr($matches[8],0,-1);$m=$this->connection->getSubstitutes()->$m;return$matches[9]==''?$this->formatValue($m,FALSE):$m.$matches[9];}die('this should be never executed');}function
delimite($value){$value=$this->connection->substitute($value);$parts=explode('.',$value);foreach($parts
as&$v){if($v!=='*')$v=$this->driver->escape($v,dibi::IDENTIFIER);}return
implode('.',$parts);}}class
DibiDataSource
extends
DibiObject
implements
IDataSource{private$connection;private$sql;private$result;private$count;private$totalCount;private$cols=array();private$sorting=array();private$conds=array();private$offset;private$limit;function
__construct($sql,DibiConnection$connection){if(strpbrk($sql," \t\r\n")===FALSE){$this->sql=$connection->getDriver()->escape($sql,dibi::IDENTIFIER);}else{$this->sql='('.$sql.') t';}$this->connection=$connection;}function
select($col,$as=NULL){if(is_array($col)){$this->cols=$col;}else{$this->cols[$col]=$as;}$this->result=NULL;return$this;}function
where($cond){if(is_array($cond)){$this->conds[]=$cond;}else{$this->conds[]=func_get_args();}$this->result=$this->count=NULL;return$this;}function
orderBy($row,$sorting='ASC'){if(is_array($row)){$this->sorting=$row;}else{$this->sorting[$row]=$sorting;}$this->result=NULL;return$this;}function
applyLimit($limit,$offset=NULL){$this->limit=$limit;$this->offset=$offset;$this->result=$this->count=NULL;return$this;}final
function
getConnection(){return$this->connection;}function
getResult(){if($this->result===NULL){$this->result=$this->connection->nativeQuery($this->__toString());}return$this->result;}function
getIterator(){return$this->getResult()->getIterator();}function
fetch(){return$this->getResult()->fetch();}function
fetchSingle(){return$this->getResult()->fetchSingle();}function
fetchAll(){return$this->getResult()->fetchAll();}function
fetchAssoc($assoc){return$this->getResult()->fetchAssoc($assoc);}function
fetchPairs($key=NULL,$value=NULL){return$this->getResult()->fetchPairs($key,$value);}function
release(){$this->result=$this->count=$this->totalCount=NULL;}function
toFluent(){return$this->connection->select('*')->from('(%SQL) t',$this->__toString());}function
toDataSource(){return
new
self($this->__toString(),$this->connection);}function
__toString(){return$this->connection->translate('
			SELECT %n',(empty($this->cols)?'*':$this->cols),'
			FROM %SQL',$this->sql,'
			%ex',$this->conds?array('WHERE %and',$this->conds):NULL,'
			%ex',$this->sorting?array('ORDER BY %by',$this->sorting):NULL,'
			%ofs %lmt',$this->offset,$this->limit);}function
count(){if($this->count===NULL){$this->count=$this->conds||$this->offset||$this->limit?(int)$this->connection->nativeQuery('SELECT COUNT(*) FROM ('.$this->__toString().') t')->fetchSingle():$this->getTotalCount();}return$this->count;}function
getTotalCount(){if($this->totalCount===NULL){$this->totalCount=(int)$this->connection->nativeQuery('SELECT COUNT(*) FROM '.$this->sql)->fetchSingle();}return$this->totalCount;}}class
DibiFluent
extends
DibiObject
implements
IDataSource{const
REMOVE=FALSE;public
static$masks=array('SELECT'=>array('SELECT','DISTINCT','FROM','WHERE','GROUP BY','HAVING','ORDER BY','LIMIT','OFFSET'),'UPDATE'=>array('UPDATE','SET','WHERE','ORDER BY','LIMIT'),'INSERT'=>array('INSERT','INTO','VALUES','SELECT'),'DELETE'=>array('DELETE','FROM','USING','WHERE','ORDER BY','LIMIT'));public
static$modifiers=array('SELECT'=>'%n','FROM'=>'%n','IN'=>'%in','VALUES'=>'%l','SET'=>'%a','WHERE'=>'%and','HAVING'=>'%and','ORDER BY'=>'%by','GROUP BY'=>'%by');public
static$separators=array('SELECT'=>',','FROM'=>',','WHERE'=>'AND','GROUP BY'=>',','HAVING'=>'AND','ORDER BY'=>',','LIMIT'=>FALSE,'OFFSET'=>FALSE,'SET'=>',','VALUES'=>',','INTO'=>FALSE);public
static$clauseSwitches=array('JOIN'=>'FROM','INNER JOIN'=>'FROM','LEFT JOIN'=>'FROM','RIGHT JOIN'=>'FROM');private$connection;private$command;private$clauses=array();private$flags=array();private$cursor;private
static$normalizer;function
__construct(DibiConnection$connection){$this->connection=$connection;if(self::$normalizer===NULL){self::$normalizer=new
DibiHashMap(array(__CLASS__,'_formatClause'));}}function
__call($clause,$args){$clause=self::$normalizer->$clause;if($this->command===NULL){if(isset(self::$masks[$clause])){$this->clauses=array_fill_keys(self::$masks[$clause],NULL);}$this->cursor=&$this->clauses[$clause];$this->cursor=array();$this->command=$clause;}if(isset(self::$clauseSwitches[$clause])){$this->cursor=&$this->clauses[self::$clauseSwitches[$clause]];}if(array_key_exists($clause,$this->clauses)){$this->cursor=&$this->clauses[$clause];if($args===array(self::REMOVE)){$this->cursor=NULL;return$this;}if(isset(self::$separators[$clause])){$sep=self::$separators[$clause];if($sep===FALSE){$this->cursor=array();}elseif(!empty($this->cursor)){$this->cursor[]=$sep;}}}else{if($args===array(self::REMOVE)){return$this;}$this->cursor[]=$clause;}if($this->cursor===NULL){$this->cursor=array();}if(count($args)===1){$arg=$args[0];if($arg===TRUE){return$this;}elseif(is_string($arg)&&preg_match('#^[a-z:_][a-z0-9_.:]*$#i',$arg)){$args=array('%n',$arg);}elseif($arg
instanceof
self){$args=array_merge(array('('),$arg->_export(),array(')'));}elseif(is_array($arg)||$arg
instanceof
Traversable){if(isset(self::$modifiers[$clause])){$args=array(self::$modifiers[$clause],$arg);}elseif(is_string(key($arg))){$args=array('%a',$arg);}}}foreach($args
as$arg)$this->cursor[]=$arg;return$this;}function
clause($clause,$remove=FALSE){$this->cursor=&$this->clauses[self::$normalizer->$clause];if($remove){trigger_error(__METHOD__.'(..., TRUE) is deprecated; use removeClause() instead.',E_USER_NOTICE);$this->cursor=NULL;}elseif($this->cursor===NULL){$this->cursor=array();}return$this;}function
removeClause($clause){$this->clauses[self::$normalizer->$clause]=NULL;return$this;}function
setFlag($flag,$value=TRUE){$flag=strtoupper($flag);if($value){$this->flags[$flag]=TRUE;}else{unset($this->flags[$flag]);}return$this;}final
function
getFlag($flag){return
isset($this->flags[strtoupper($flag)]);}final
function
getCommand(){return$this->command;}final
function
getConnection(){return$this->connection;}function
execute($return=NULL){$res=$this->connection->query($this->_export());return$return===dibi::IDENTIFIER?$this->connection->getInsertId():$res;}function
fetch(){if($this->command==='SELECT'){return$this->connection->query($this->_export(NULL,array('%lmt',1)))->fetch();}else{return$this->connection->query($this->_export())->fetch();}}function
fetchSingle(){if($this->command==='SELECT'){return$this->connection->query($this->_export(NULL,array('%lmt',1)))->fetchSingle();}else{return$this->connection->query($this->_export())->fetchSingle();}}function
fetchAll($offset=NULL,$limit=NULL){return$this->connection->query($this->_export(NULL,array('%ofs %lmt',$offset,$limit)))->fetchAll();}function
fetchAssoc($assoc){return$this->connection->query($this->_export())->fetchAssoc($assoc);}function
fetchPairs($key=NULL,$value=NULL){return$this->connection->query($this->_export())->fetchPairs($key,$value);}function
getIterator($offset=NULL,$limit=NULL){return$this->connection->query($this->_export(NULL,array('%ofs %lmt',$offset,$limit)))->getIterator();}function
test($clause=NULL){return$this->connection->test($this->_export($clause));}function
count(){return(int)$this->connection->query('SELECT COUNT(*) FROM (%ex',$this->_export(),') AS [data]')->fetchSingle();}function
toDataSource(){return
new
DibiDataSource($this->connection->translate($this->_export()),$this->connection);}final
function
__toString(){return$this->connection->translate($this->_export());}protected
function
_export($clause=NULL,$args=array()){if($clause===NULL){$data=$this->clauses;}else{$clause=self::$normalizer->$clause;if(array_key_exists($clause,$this->clauses)){$data=array($clause=>$this->clauses[$clause]);}else{return
array();}}foreach($data
as$clause=>$statement){if($statement!==NULL){$args[]=$clause;if($clause===$this->command&&$this->flags){$args[]=implode(' ',array_keys($this->flags));}foreach($statement
as$arg)$args[]=$arg;}}return$args;}static
function
_formatClause($s){if($s==='order'||$s==='group'){$s.='By';trigger_error("Did you mean '$s'?",E_USER_NOTICE);}return
strtoupper(preg_replace('#[a-z](?=[A-Z])#','$0 ',$s));}function
__clone(){foreach($this->clauses
as$clause=>$val){$this->clauses[$clause]=&$val;unset($val);}$this->cursor=&$foo;}}class
DibiDatabaseInfo
extends
DibiObject{private$reflector;private$name;private$tables;function
__construct(IDibiReflector$reflector,$name){$this->reflector=$reflector;$this->name=$name;}function
getName(){return$this->name;}function
getTables(){$this->init();return
array_values($this->tables);}function
getTableNames(){$this->init();$res=array();foreach($this->tables
as$table){$res[]=$table->getName();}return$res;}function
getTable($name){$this->init();$l=strtolower($name);if(isset($this->tables[$l])){return$this->tables[$l];}else{throw
new
DibiException("Database '$this->name' has no table '$name'.");}}function
hasTable($name){$this->init();return
isset($this->tables[strtolower($name)]);}protected
function
init(){if($this->tables===NULL){$this->tables=array();foreach($this->reflector->getTables()as$info){$this->tables[strtolower($info['name'])]=new
DibiTableInfo($this->reflector,$info);}}}}class
DibiTableInfo
extends
DibiObject{private$reflector;private$name;private$view;private$columns;private$foreignKeys;private$indexes;private$primaryKey;function
__construct(IDibiReflector$reflector,array$info){$this->reflector=$reflector;$this->name=$info['name'];$this->view=!empty($info['view']);}function
getName(){return$this->name;}function
isView(){return$this->view;}function
getColumns(){$this->initColumns();return
array_values($this->columns);}function
getColumnNames(){$this->initColumns();$res=array();foreach($this->columns
as$column){$res[]=$column->getName();}return$res;}function
getColumn($name){$this->initColumns();$l=strtolower($name);if(isset($this->columns[$l])){return$this->columns[$l];}else{throw
new
DibiException("Table '$this->name' has no column '$name'.");}}function
hasColumn($name){$this->initColumns();return
isset($this->columns[strtolower($name)]);}function
getForeignKeys(){$this->initForeignKeys();return$this->foreignKeys;}function
getIndexes(){$this->initIndexes();return$this->indexes;}function
getPrimaryKey(){$this->initIndexes();return$this->primaryKey;}protected
function
initColumns(){if($this->columns===NULL){$this->columns=array();foreach($this->reflector->getColumns($this->name)as$info){$this->columns[strtolower($info['name'])]=new
DibiColumnInfo($this->reflector,$info);}}}protected
function
initIndexes(){if($this->indexes===NULL){$this->initColumns();$this->indexes=array();foreach($this->reflector->getIndexes($this->name)as$info){foreach($info['columns']as$key=>$name){$info['columns'][$key]=$this->columns[strtolower($name)];}$this->indexes[strtolower($info['name'])]=new
DibiIndexInfo($info);if(!empty($info['primary'])){$this->primaryKey=$this->indexes[strtolower($info['name'])];}}}}protected
function
initForeignKeys(){throw
new
NotImplementedException;}}class
DibiResultInfo
extends
DibiObject{private$driver;private$columns;private$names;function
__construct(IDibiResultDriver$driver){$this->driver=$driver;}function
getColumns(){$this->initColumns();return
array_values($this->columns);}function
getColumnNames($fullNames=FALSE){$this->initColumns();$res=array();foreach($this->columns
as$column){$res[]=$fullNames?$column->getFullName():$column->getName();}return$res;}function
getColumn($name){$this->initColumns();$l=strtolower($name);if(isset($this->names[$l])){return$this->names[$l];}else{throw
new
DibiException("Result set has no column '$name'.");}}function
hasColumn($name){$this->initColumns();return
isset($this->names[strtolower($name)]);}protected
function
initColumns(){if($this->columns===NULL){$this->columns=array();$reflector=$this->driver
instanceof
IDibiReflector?$this->driver:NULL;foreach($this->driver->getResultColumns()as$info){$this->columns[]=$this->names[$info['name']]=new
DibiColumnInfo($reflector,$info);}}}}class
DibiColumnInfo
extends
DibiObject{private
static$types;private$reflector;private$info;function
__construct(IDibiReflector$reflector=NULL,array$info){$this->reflector=$reflector;$this->info=$info;}function
getName(){return$this->info['name'];}function
getFullName(){return
isset($this->info['fullname'])?$this->info['fullname']:NULL;}function
hasTable(){return!empty($this->info['table']);}function
getTable(){if(empty($this->info['table'])||!$this->reflector){throw
new
DibiException("Table is unknown or not available.");}return
new
DibiTableInfo($this->reflector,array('name'=>$this->info['table']));}function
getTableName(){return
isset($this->info['table'])?$this->info['table']:NULL;}function
getType(){if(self::$types===NULL){self::$types=new
DibiHashMap(array(__CLASS__,'detectType'));}return
self::$types->{$this->info['nativetype']};}function
getNativeType(){return$this->info['nativetype'];}function
getSize(){return
isset($this->info['size'])?(int)$this->info['size']:NULL;}function
isUnsigned(){return
isset($this->info['unsigned'])?(bool)$this->info['unsigned']:NULL;}function
isNullable(){return
isset($this->info['nullable'])?(bool)$this->info['nullable']:NULL;}function
isAutoIncrement(){return
isset($this->info['autoincrement'])?(bool)$this->info['autoincrement']:NULL;}function
getDefault(){return
isset($this->info['default'])?$this->info['default']:NULL;}function
getVendorInfo($key){return
isset($this->info['vendor'][$key])?$this->info['vendor'][$key]:NULL;}static
function
detectType($type){static$patterns=array('BYTEA|BLOB|BIN'=>dibi::BINARY,'TEXT|CHAR|BIGINT|LONGLONG'=>dibi::TEXT,'BYTE|COUNTER|SERIAL|INT|LONG'=>dibi::INTEGER,'CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER'=>dibi::FLOAT,'^TIME$'=>dibi::TIME,'TIME'=>dibi::DATETIME,'YEAR|DATE'=>dibi::DATE,'BOOL|BIT'=>dibi::BOOL);foreach($patterns
as$s=>$val){if(preg_match("#$s#i",$type)){return$val;}}return
dibi::TEXT;}}class
DibiForeignKeyInfo
extends
DibiObject{private$name;private$references;function
__construct($name,array$references){$this->name=$name;$this->references=$references;}function
getName(){return$this->name;}function
getReferences(){return$this->references;}}class
DibiIndexInfo
extends
DibiObject{private$info;function
__construct(array$info){$this->info=$info;}function
getName(){return$this->info['name'];}function
getColumns(){return$this->info['columns'];}function
isUnique(){return!empty($this->info['unique']);}function
isPrimary(){return!empty($this->info['primary']);}}class
DibiProfiler
extends
DibiObject
implements
IDibiProfiler,IDebugPanel{static
public$maxQueries=30;static
public$maxLength=1000;private$file;public$useFirebug;public$explainQuery=TRUE;private$filter=self::ALL;public
static$tickets=array();public
static$fireTable=array(array('Time','SQL Statement','Rows','Connection'));function
__construct(array$config){if(is_callable('Nette\Debug::addPanel')){call_user_func('Nette\Debug::addPanel',$this);}elseif(is_callable('NDebug::addPanel')){NDebug::addPanel($this);}elseif(is_callable('Debug::addPanel')){Debug::addPanel($this);}$this->useFirebug=isset($_SERVER['HTTP_USER_AGENT'])&&strpos($_SERVER['HTTP_USER_AGENT'],'FirePHP/');if(isset($config['file'])){$this->setFile($config['file']);}if(isset($config['filter'])){$this->setFilter($config['filter']);}if(isset($config['explain'])){$this->explainQuery=(bool)$config['explain'];}}function
setFile($file){$this->file=$file;return$this;}function
setFilter($filter){$this->filter=(int)$filter;return$this;}function
before(DibiConnection$connection,$event,$sql=NULL){$rc=new
ReflectionClass('dibi');$dibiDir=dirname($rc->getFileName()).DIRECTORY_SEPARATOR;$source=NULL;foreach(debug_backtrace(FALSE)as$row){if(isset($row['file'])&&is_file($row['file'])&&strpos($row['file'],$dibiDir)!==0){$source=array($row['file'],(int)$row['line']);break;}}if($event&self::QUERY)dibi::$numOfQueries++;dibi::$elapsedTime=FALSE;self::$tickets[]=array($connection,$event,trim($sql),-microtime(TRUE),NULL,$source);end(self::$tickets);return
key(self::$tickets);}function
after($ticket,$res=NULL){if(!isset(self::$tickets[$ticket])){throw
new
InvalidArgumentException('Bad ticket number.');}$ticket=&self::$tickets[$ticket];$ticket[3]+=microtime(TRUE);list($connection,$event,$sql,$time,,$source)=$ticket;dibi::$elapsedTime=$time;dibi::$totalTime+=$time;if(($event&$this->filter)===0)return;if($event&self::QUERY){try{$ticket[4]=$count=$res
instanceof
DibiResult?count($res):'-';}catch(Exception$e){$count='?';}if(count(self::$fireTable)<self::$maxQueries){self::$fireTable[]=array(sprintf('%0.3f',$time*1000),strlen($sql)>self::$maxLength?substr($sql,0,self::$maxLength).'...':$sql,$count,$connection->getConfig('driver').'/'.$connection->getConfig('name'));if($this->useFirebug&&!headers_sent()){header('X-Wf-Protocol-dibi: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');header('X-Wf-dibi-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');header('X-Wf-dibi-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');$payload=json_encode(array(array('Type'=>'TABLE','Label'=>'dibi profiler ('.dibi::$numOfQueries.' SQL queries took '.sprintf('%0.3f',dibi::$totalTime*1000).' ms)'),self::$fireTable));foreach(str_split($payload,4990)as$num=>$s){$num++;header("X-Wf-dibi-1-1-d$num: |$s|\\");}header("X-Wf-dibi-1-1-d$num: |$s|");}}if($this->file){$this->writeFile("OK: ".$sql.($res
instanceof
DibiResult?";\n-- rows: ".$count:'')."\n-- takes: ".sprintf('%0.3f',$time*1000).' ms'."\n-- source: ".implode(':',$source)."\n-- driver: ".$connection->getConfig('driver').'/'.$connection->getConfig('name')."\n-- ".date('Y-m-d H:i:s')."\n\n");}}}function
exception(DibiDriverException$exception){if((self::EXCEPTION&$this->filter)===0)return;if($this->useFirebug){}if($this->file){$message=$exception->getMessage();$code=$exception->getCode();if($code){$message="[$code] $message";}$this->writeFile("ERROR: $message"."\n-- SQL: ".dibi::$sql."\n-- driver: ".";\n-- ".date('Y-m-d H:i:s')."\n\n");}}private
function
writeFile($message){$handle=fopen($this->file,'a');if(!$handle)return;flock($handle,LOCK_EX);fwrite($handle,$message);fclose($handle);}function
getTab(){return'<span title="dibi profiler"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC" />'.dibi::$numOfQueries.' queries</span>';}function
getPanel(){$s=NULL;$h='htmlSpecialChars';foreach(self::$tickets
as$i=>$ticket){list($connection,$event,$sql,$time,$count,$source)=$ticket;if(!($event&self::QUERY))continue;$explain=NULL;if($this->explainQuery&&$event===self::SELECT){try{$explain=dibi::dump($connection->setProfiler(NULL)->nativeQuery('EXPLAIN '.$sql),TRUE);}catch(DibiException$e){}$connection->setProfiler($this);}$s.='<tr><td>'.sprintf('%0.3f',$time*1000);if($explain){$s.="<br /><a href='#' class='nette-toggler' rel='#nette-debug-DibiProfiler-row-$i'>explain&nbsp;&#x25ba;</a>";}$s.='</td><td class="dibi-sql">'.dibi::dump(strlen($sql)>self::$maxLength?substr($sql,0,self::$maxLength).'...':$sql,TRUE);if($explain){$s.="<div id='nette-debug-DibiProfiler-row-$i' class='nette-collapsed'>{$explain}</div>";}if($source){list($file,$line)=$source;$s.="<span class='dibi-source' title='{$h($file)}:$line'>{$h(basename(dirname($file)).'/'.basename($file))}:$line</span>";}$s.="</td><td>{$count}</td><td>{$h($connection->getConfig('driver').'/'.$connection->getConfig('name'))}</td></tr>";}return$s===NULL?'':'<style> #nette-debug-DibiProfiler td.dibi-sql { background: white !important }
			#nette-debug-DibiProfiler .dibi-source { color: #BBB !important }
			#nette-debug-DibiProfiler tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>

			<h1>Queries: '.dibi::$numOfQueries.(dibi::$totalTime===NULL?'':', time: '.sprintf('%0.3f',dibi::$totalTime*1000).' ms').'</h1>
			<div class="nette-inner">
			<table>
				<th>Time</th><th>SQL Statement</th><th>Rows</th><th>Connection</th>'.$s.'
			</table>
			</div>';}function
getId(){return
get_class($this);}}class
DibiVariable
extends
DibiDateTime{function
__construct($val){parent::__construct($val);trigger_error(__CLASS__.' is deprecated; use class DateTime instead.',E_USER_WARNING);}}class
dibi{const
TEXT='s',BINARY='bin',BOOL='b',INTEGER='i',FLOAT='f',DATE='d',DATETIME='t',TIME='t';const
IDENTIFIER='n';const
FIELD_TEXT=dibi::TEXT,FIELD_BINARY=dibi::BINARY,FIELD_BOOL=dibi::BOOL,FIELD_INTEGER=dibi::INTEGER,FIELD_FLOAT=dibi::FLOAT,FIELD_DATE=dibi::DATE,FIELD_DATETIME=dibi::DATETIME,FIELD_TIME=dibi::TIME;const
VERSION='1.5-rc1',REVISION='8a899c7 released on 2011-02-23';const
ASC='ASC',DESC='DESC';private
static$registry=array();private
static$connection;private
static$handlers=array();public
static$sql;public
static$elapsedTime;public
static$totalTime;public
static$numOfQueries=0;public
static$defaultDriver='mysql';final
function
__construct(){throw
new
LogicException("Cannot instantiate static class ".get_class($this));}static
function
connect($config=array(),$name=0){return
self::$connection=self::$registry[$name]=new
DibiConnection($config,$name);}static
function
disconnect(){self::getConnection()->disconnect();}static
function
isConnected(){return(self::$connection!==NULL)&&self::$connection->isConnected();}static
function
getConnection($name=NULL){if($name===NULL){if(self::$connection===NULL){throw
new
DibiException('Dibi is not connected to database.');}return
self::$connection;}if(!isset(self::$registry[$name])){throw
new
DibiException("There is no connection named '$name'.");}return
self::$registry[$name];}static
function
setConnection(DibiConnection$connection){return
self::$connection=$connection;}static
function
activate($name){self::$connection=self::getConnection($name);}static
function
getProfiler(){return
self::getConnection()->getProfiler();}static
function
query($args){$args=func_get_args();return
self::getConnection()->query($args);}static
function
nativeQuery($sql){return
self::getConnection()->nativeQuery($sql);}static
function
test($args){$args=func_get_args();return
self::getConnection()->test($args);}static
function
dataSource($args){$args=func_get_args();return
self::getConnection()->dataSource($args);}static
function
fetch($args){$args=func_get_args();return
self::getConnection()->query($args)->fetch();}static
function
fetchAll($args){$args=func_get_args();return
self::getConnection()->query($args)->fetchAll();}static
function
fetchSingle($args){$args=func_get_args();return
self::getConnection()->query($args)->fetchSingle();}static
function
fetchPairs($args){$args=func_get_args();return
self::getConnection()->query($args)->fetchPairs();}static
function
getAffectedRows(){return
self::getConnection()->getAffectedRows();}static
function
affectedRows(){return
self::getConnection()->getAffectedRows();}static
function
getInsertId($sequence=NULL){return
self::getConnection()->getInsertId($sequence);}static
function
insertId($sequence=NULL){return
self::getConnection()->getInsertId($sequence);}static
function
begin($savepoint=NULL){self::getConnection()->begin($savepoint);}static
function
commit($savepoint=NULL){self::getConnection()->commit($savepoint);}static
function
rollback($savepoint=NULL){self::getConnection()->rollback($savepoint);}static
function
getDatabaseInfo(){return
self::getConnection()->getDatabaseInfo();}static
function
loadFile($file){return
self::getConnection()->loadFile($file);}static
function
__callStatic($name,$args){return
call_user_func_array(array(self::getConnection(),$name),$args);}static
function
command(){return
self::getConnection()->command();}static
function
select($args){$args=func_get_args();return
call_user_func_array(array(self::getConnection(),'select'),$args);}static
function
update($table,$args){return
self::getConnection()->update($table,$args);}static
function
insert($table,$args){return
self::getConnection()->insert($table,$args);}static
function
delete($table){return
self::getConnection()->delete($table);}static
function
datetime($time=NULL){trigger_error(__METHOD__.'() is deprecated; create DibiDateTime object instead.',E_USER_WARNING);return
new
DibiDateTime($time);}static
function
date($date=NULL){trigger_error(__METHOD__.'() is deprecated; create DibiDateTime object instead.',E_USER_WARNING);return
new
DibiDateTime($date);}static
function
getSubstitutes(){return
self::getConnection()->getSubstitutes();}static
function
addSubst($expr,$subst){trigger_error(__METHOD__.'() is deprecated; use dibi::getSubstitutes()->expr = val; instead.',E_USER_WARNING);self::getSubstitutes()->$expr=$subst;}static
function
removeSubst($expr){trigger_error(__METHOD__.'() is deprecated; use unset(dibi::getSubstitutes()->expr) instead.',E_USER_WARNING);$substitutes=self::getSubstitutes();if($expr===TRUE){foreach($substitutes
as$expr=>$foo){unset($substitutes->$expr);}}else{unset($substitutes->$expr);}}static
function
setSubstFallback($callback){trigger_error(__METHOD__.'() is deprecated; use dibi::getSubstitutes()->setCallback() instead.',E_USER_WARNING);self::getSubstitutes()->setCallback($callback);}static
function
dump($sql=NULL,$return=FALSE){ob_start();if($sql
instanceof
DibiResult){$sql->dump();}else{if($sql===NULL)$sql=self::$sql;static$keywords1='SELECT|UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';static$keywords2='ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE';$sql=" $sql ";$sql=preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i","\n\$1",$sql);$sql=preg_replace('#[ \t]{2,}#'," ",$sql);$sql=wordwrap($sql,100);$sql=preg_replace("#([ \t]*\r?\n){2,}#","\n",$sql);if(PHP_SAPI==='cli'){echo
trim($sql)."\n\n";}else{$sql=htmlSpecialChars($sql);$sql=preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is",array('dibi','highlightCallback'),$sql);echo'<pre class="dump">',trim($sql),"</pre>\n";}}if($return){return
ob_get_clean();}else{ob_end_flush();}}private
static
function
highlightCallback($matches){if(!empty($matches[1]))return'<em style="color:gray">'.$matches[1].'</em>';if(!empty($matches[2]))return'<strong style="color:red">'.$matches[2].'</strong>';if(!empty($matches[3]))return'<strong style="color:blue">'.$matches[3].'</strong>';if(!empty($matches[4]))return'<strong style="color:green">'.$matches[4].'</strong>';}}class
DibiMySqlReflector
extends
DibiObject
implements
IDibiReflector{private$driver;function
__construct(IDibiDriver$driver){$this->driver=$driver;}function
getTables(){$res=$this->driver->query("SHOW FULL TABLES");$tables=array();while($row=$res->fetch(FALSE)){$tables[]=array('name'=>$row[0],'view'=>isset($row[1])&&$row[1]==='VIEW');}return$tables;}function
getColumns($table){$res=$this->driver->query("SHOW FULL COLUMNS FROM `$table`");$columns=array();while($row=$res->fetch(TRUE)){$type=explode('(',$row['Type']);$columns[]=array('name'=>$row['Field'],'table'=>$table,'nativetype'=>strtoupper($type[0]),'size'=>isset($type[1])?(int)$type[1]:NULL,'unsigned'=>(bool)strstr($row['Type'],'unsigned'),'nullable'=>$row['Null']==='YES','default'=>$row['Default'],'autoincrement'=>$row['Extra']==='auto_increment','vendor'=>$row);}return$columns;}function
getIndexes($table){$res=$this->driver->query("SHOW INDEX FROM `$table`");$indexes=array();while($row=$res->fetch(TRUE)){$indexes[$row['Key_name']]['name']=$row['Key_name'];$indexes[$row['Key_name']]['unique']=!$row['Non_unique'];$indexes[$row['Key_name']]['primary']=$row['Key_name']==='PRIMARY';$indexes[$row['Key_name']]['columns'][$row['Seq_in_index']-1]=$row['Column_name'];}return
array_values($indexes);}function
getForeignKeys($table){throw
new
NotImplementedException;}}class
DibiMySqlDriver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver{const
ERROR_ACCESS_DENIED=1045;const
ERROR_DUPLICATE_ENTRY=1062;const
ERROR_DATA_TRUNCATED=1265;private$connection;private$resultSet;private$buffered;function
__construct(){if(!extension_loaded('mysql')){throw
new
NotSupportedException("PHP extension 'mysql' is not loaded.");}}function
connect(array&$config){if(isset($config['resource'])){$this->connection=$config['resource'];}else{DibiConnection::alias($config,'flags','options');if(!isset($config['charset']))$config['charset']='utf8';if(!isset($config['username']))$config['username']=ini_get('mysql.default_user');if(!isset($config['password']))$config['password']=ini_get('mysql.default_password');if(!isset($config['host'])){$host=ini_get('mysql.default_host');if($host){$config['host']=$host;$config['port']=ini_get('mysql.default_port');}else{if(!isset($config['socket']))$config['socket']=ini_get('mysql.default_socket');$config['host']=NULL;}}if(empty($config['socket'])){$host=$config['host'].(empty($config['port'])?'':':'.$config['port']);}else{$host=':'.$config['socket'];}if(empty($config['persistent'])){$this->connection=@mysql_connect($host,$config['username'],$config['password'],TRUE,$config['flags']);}else{$this->connection=@mysql_pconnect($host,$config['username'],$config['password'],$config['flags']);}}if(!is_resource($this->connection)){throw
new
DibiDriverException(mysql_error(),mysql_errno());}if(isset($config['charset'])){$ok=FALSE;if(function_exists('mysql_set_charset')){$ok=@mysql_set_charset($config['charset'],$this->connection);}if(!$ok){$this->query("SET NAMES '$config[charset]'");}}if(isset($config['database'])){if(!@mysql_select_db($config['database'],$this->connection)){throw
new
DibiDriverException(mysql_error($this->connection),mysql_errno($this->connection));}}if(isset($config['sqlmode'])){$this->query("SET sql_mode='$config[sqlmode]'");}$this->query("SET time_zone='".date('P')."'");$this->buffered=empty($config['unbuffered']);}function
disconnect(){mysql_close($this->connection);}function
query($sql){if($this->buffered){$res=@mysql_query($sql,$this->connection);}else{$res=@mysql_unbuffered_query($sql,$this->connection);}if(mysql_errno($this->connection)){throw
new
DibiDriverException(mysql_error($this->connection),mysql_errno($this->connection),$sql);}elseif(is_resource($res)){return$this->createResultDriver($res);}}function
getInfo(){$res=array();preg_match_all('#(.+?): +(\d+) *#',mysql_info($this->connection),$matches,PREG_SET_ORDER);if(preg_last_error())throw
new
DibiPcreException;foreach($matches
as$m){$res[$m[1]]=(int)$m[2];}return$res;}function
getAffectedRows(){return
mysql_affected_rows($this->connection);}function
getInsertId($sequence){return
mysql_insert_id($this->connection);}function
begin($savepoint=NULL){$this->query($savepoint?"SAVEPOINT $savepoint":'START TRANSACTION');}function
commit($savepoint=NULL){$this->query($savepoint?"RELEASE SAVEPOINT $savepoint":'COMMIT');}function
rollback($savepoint=NULL){$this->query($savepoint?"ROLLBACK TO SAVEPOINT $savepoint":'ROLLBACK');}function
getResource(){return$this->connection;}function
getReflector(){return
new
DibiMySqlReflector($this);}function
createResultDriver($resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:return"'".mysql_real_escape_string($value,$this->connection)."'";case
dibi::BINARY:return"_binary'".mysql_real_escape_string($value,$this->connection)."'";case
dibi::IDENTIFIER:return'`'.str_replace('`','``',$value).'`';case
dibi::BOOL:return$value?1:0;case
dibi::DATE:return$value
instanceof
DateTime?$value->format("'Y-m-d'"):date("'Y-m-d'",$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format("'Y-m-d H:i:s'"):date("'Y-m-d H:i:s'",$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){$value=addcslashes(str_replace('\\','\\\\',$value),"\x00\n\r\\'%_");return($pos<=0?"'%":"'").$value.($pos>=0?"%'":"'");}function
unescape($value,$type){if($type===dibi::BINARY){return$value;}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}function
__destruct(){$this->resultSet&&@$this->free();}function
getRowCount(){if(!$this->buffered){throw
new
NotSupportedException('Row count is not available for unbuffered queries.');}return
mysql_num_rows($this->resultSet);}function
fetch($assoc){return
mysql_fetch_array($this->resultSet,$assoc?MYSQL_ASSOC:MYSQL_NUM);}function
seek($row){if(!$this->buffered){throw
new
NotSupportedException('Cannot seek an unbuffered result set.');}return
mysql_data_seek($this->resultSet,$row);}function
free(){mysql_free_result($this->resultSet);$this->resultSet=NULL;}function
getResultColumns(){$count=mysql_num_fields($this->resultSet);$columns=array();for($i=0;$i<$count;$i++){$row=(array)mysql_fetch_field($this->resultSet,$i);$columns[]=array('name'=>$row['name'],'table'=>$row['table'],'fullname'=>$row['table']?$row['table'].'.'.$row['name']:$row['name'],'nativetype'=>strtoupper($row['type']),'vendor'=>$row);}return$columns;}function
getResultResource(){return$this->resultSet;}}class
DibiMySqliDriver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver{const
ERROR_ACCESS_DENIED=1045;const
ERROR_DUPLICATE_ENTRY=1062;const
ERROR_DATA_TRUNCATED=1265;private$connection;private$resultSet;private$buffered;function
__construct(){if(!extension_loaded('mysqli')){throw
new
NotSupportedException("PHP extension 'mysqli' is not loaded.");}}function
connect(array&$config){mysqli_report(MYSQLI_REPORT_OFF);if(isset($config['resource'])){$this->connection=$config['resource'];}else{if(!isset($config['charset']))$config['charset']='utf8';if(!isset($config['username']))$config['username']=ini_get('mysqli.default_user');if(!isset($config['password']))$config['password']=ini_get('mysqli.default_pw');if(!isset($config['socket']))$config['socket']=ini_get('mysqli.default_socket');if(!isset($config['port']))$config['port']=NULL;if(!isset($config['host'])){$host=ini_get('mysqli.default_host');if($host){$config['host']=$host;$config['port']=ini_get('mysqli.default_port');}else{$config['host']=NULL;$config['port']=NULL;}}$foo=&$config['flags'];$foo=&$config['database'];$this->connection=mysqli_init();if(isset($config['options'])){if(is_scalar($config['options'])){$config['flags']=$config['options'];trigger_error(__CLASS__.": configuration item 'options' must be array; for constants MYSQLI_CLIENT_* use 'flags'.",E_USER_NOTICE);}else{foreach((array)$config['options']as$key=>$value){mysqli_options($this->connection,$key,$value);}}}@mysqli_real_connect($this->connection,(empty($config['persistent'])?'':'p:').$config['host'],$config['username'],$config['password'],$config['database'],$config['port'],$config['socket'],$config['flags']);if($errno=mysqli_connect_errno()){throw
new
DibiDriverException(mysqli_connect_error(),$errno);}}if(isset($config['charset'])){$ok=FALSE;if(version_compare(PHP_VERSION,'5.1.5','>=')){$ok=@mysqli_set_charset($this->connection,$config['charset']);}if(!$ok){$this->query("SET NAMES '$config[charset]'");}}if(isset($config['sqlmode'])){$this->query("SET sql_mode='$config[sqlmode]'");}$this->query("SET time_zone='".date('P')."'");$this->buffered=empty($config['unbuffered']);}function
disconnect(){mysqli_close($this->connection);}function
query($sql){$res=@mysqli_query($this->connection,$sql,$this->buffered?MYSQLI_STORE_RESULT:MYSQLI_USE_RESULT);if(mysqli_errno($this->connection)){throw
new
DibiDriverException(mysqli_error($this->connection),mysqli_errno($this->connection),$sql);}elseif(is_object($res)){return$this->createResultDriver($res);}}function
getInfo(){$res=array();preg_match_all('#(.+?): +(\d+) *#',mysqli_info($this->connection),$matches,PREG_SET_ORDER);if(preg_last_error())throw
new
DibiPcreException;foreach($matches
as$m){$res[$m[1]]=(int)$m[2];}return$res;}function
getAffectedRows(){return
mysqli_affected_rows($this->connection);}function
getInsertId($sequence){return
mysqli_insert_id($this->connection);}function
begin($savepoint=NULL){$this->query($savepoint?"SAVEPOINT $savepoint":'START TRANSACTION');}function
commit($savepoint=NULL){$this->query($savepoint?"RELEASE SAVEPOINT $savepoint":'COMMIT');}function
rollback($savepoint=NULL){$this->query($savepoint?"ROLLBACK TO SAVEPOINT $savepoint":'ROLLBACK');}function
getResource(){return$this->connection;}function
getReflector(){return
new
DibiMySqlReflector($this);}function
createResultDriver(mysqli_result$resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:return"'".mysqli_real_escape_string($this->connection,$value)."'";case
dibi::BINARY:return"_binary'".mysqli_real_escape_string($this->connection,$value)."'";case
dibi::IDENTIFIER:return'`'.str_replace('`','``',$value).'`';case
dibi::BOOL:return$value?1:0;case
dibi::DATE:return$value
instanceof
DateTime?$value->format("'Y-m-d'"):date("'Y-m-d'",$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format("'Y-m-d H:i:s'"):date("'Y-m-d H:i:s'",$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){$value=addcslashes(str_replace('\\','\\\\',$value),"\x00\n\r\\'%_");return($pos<=0?"'%":"'").$value.($pos>=0?"%'":"'");}function
unescape($value,$type){if($type===dibi::BINARY){return$value;}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');}function
__destruct(){$this->resultSet&&@$this->free();}function
getRowCount(){if(!$this->buffered){throw
new
NotSupportedException('Row count is not available for unbuffered queries.');}return
mysqli_num_rows($this->resultSet);}function
fetch($assoc){return
mysqli_fetch_array($this->resultSet,$assoc?MYSQLI_ASSOC:MYSQLI_NUM);}function
seek($row){if(!$this->buffered){throw
new
NotSupportedException('Cannot seek an unbuffered result set.');}return
mysqli_data_seek($this->resultSet,$row);}function
free(){mysqli_free_result($this->resultSet);$this->resultSet=NULL;}function
getResultColumns(){static$types;if(empty($types)){$consts=get_defined_constants(TRUE);foreach($consts['mysqli']as$key=>$value){if(strncmp($key,'MYSQLI_TYPE_',12)===0){$types[$value]=substr($key,12);}}$types[MYSQLI_TYPE_TINY]=$types[MYSQLI_TYPE_SHORT]=$types[MYSQLI_TYPE_LONG]='INT';}$count=mysqli_num_fields($this->resultSet);$columns=array();for($i=0;$i<$count;$i++){$row=(array)mysqli_fetch_field_direct($this->resultSet,$i);$columns[]=array('name'=>$row['name'],'table'=>$row['orgtable'],'fullname'=>$row['table']?$row['table'].'.'.$row['name']:$row['name'],'nativetype'=>$types[$row['type']],'vendor'=>$row);}return$columns;}function
getResultResource(){return$this->resultSet;}}class
DibiOdbcDriver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver,IDibiReflector{private$connection;private$resultSet;private$affectedRows=FALSE;private$row=0;function
__construct(){if(!extension_loaded('odbc')){throw
new
NotSupportedException("PHP extension 'odbc' is not loaded.");}}function
connect(array&$config){if(isset($config['resource'])){$this->connection=$config['resource'];}else{if(!isset($config['username']))$config['username']=ini_get('odbc.default_user');if(!isset($config['password']))$config['password']=ini_get('odbc.default_pw');if(!isset($config['dsn']))$config['dsn']=ini_get('odbc.default_db');if(empty($config['persistent'])){$this->connection=@odbc_connect($config['dsn'],$config['username'],$config['password']);}else{$this->connection=@odbc_pconnect($config['dsn'],$config['username'],$config['password']);}}if(!is_resource($this->connection)){throw
new
DibiDriverException(odbc_errormsg().' '.odbc_error());}}function
disconnect(){odbc_close($this->connection);}function
query($sql){$this->affectedRows=FALSE;$res=@odbc_exec($this->connection,$sql);if($res===FALSE){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection),0,$sql);}elseif(is_resource($res)){$this->affectedRows=odbc_num_rows($res);return$this->createResultDriver($res);}}function
getAffectedRows(){return$this->affectedRows;}function
getInsertId($sequence){throw
new
NotSupportedException('ODBC does not support autoincrementing.');}function
begin($savepoint=NULL){if(!odbc_autocommit($this->connection,FALSE)){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection));}}function
commit($savepoint=NULL){if(!odbc_commit($this->connection)){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection));}odbc_autocommit($this->connection,TRUE);}function
rollback($savepoint=NULL){if(!odbc_rollback($this->connection)){throw
new
DibiDriverException(odbc_errormsg($this->connection).' '.odbc_error($this->connection));}odbc_autocommit($this->connection,TRUE);}function
inTransaction(){return!odbc_autocommit($this->connection);}function
getResource(){return$this->connection;}function
getReflector(){return$this;}function
createResultDriver($resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:case
dibi::BINARY:return"'".str_replace("'","''",$value)."'";case
dibi::IDENTIFIER:return'['.str_replace(array('[',']'),array('[[',']]'),$value).']';case
dibi::BOOL:return$value?1:0;case
dibi::DATE:return$value
instanceof
DateTime?$value->format("#m/d/Y#"):date("#m/d/Y#",$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format("#m/d/Y H:i:s#"):date("#m/d/Y H:i:s#",$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){$value=strtr($value,array("'"=>"''",'%'=>'[%]','_'=>'[_]','['=>'[[]'));return($pos<=0?"'%":"'").$value.($pos>=0?"%'":"'");}function
unescape($value,$type){if($type===dibi::BINARY){return$value;}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit>=0){$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';}if($offset)throw
new
NotSupportedException('Offset is not implemented in driver odbc.');}function
__destruct(){$this->resultSet&&@$this->free();}function
getRowCount(){return
odbc_num_rows($this->resultSet);}function
fetch($assoc){if($assoc){return
odbc_fetch_array($this->resultSet,++$this->row);}else{$set=$this->resultSet;if(!odbc_fetch_row($set,++$this->row))return
FALSE;$count=odbc_num_fields($set);$cols=array();for($i=1;$i<=$count;$i++)$cols[]=odbc_result($set,$i);return$cols;}}function
seek($row){$this->row=$row;return
TRUE;}function
free(){odbc_free_result($this->resultSet);$this->resultSet=NULL;}function
getResultColumns(){$count=odbc_num_fields($this->resultSet);$columns=array();for($i=1;$i<=$count;$i++){$columns[]=array('name'=>odbc_field_name($this->resultSet,$i),'table'=>NULL,'fullname'=>odbc_field_name($this->resultSet,$i),'nativetype'=>odbc_field_type($this->resultSet,$i));}return$columns;}function
getResultResource(){return$this->resultSet;}function
getTables(){$res=odbc_tables($this->connection);$tables=array();while($row=odbc_fetch_array($res)){if($row['TABLE_TYPE']==='TABLE'||$row['TABLE_TYPE']==='VIEW'){$tables[]=array('name'=>$row['TABLE_NAME'],'view'=>$row['TABLE_TYPE']==='VIEW');}}odbc_free_result($res);return$tables;}function
getColumns($table){$res=odbc_columns($this->connection);$columns=array();while($row=odbc_fetch_array($res)){if($row['TABLE_NAME']===$table){$columns[]=array('name'=>$row['COLUMN_NAME'],'table'=>$table,'nativetype'=>$row['TYPE_NAME'],'size'=>$row['COLUMN_SIZE'],'nullable'=>(bool)$row['NULLABLE'],'default'=>$row['COLUMN_DEF']);}}odbc_free_result($res);return$columns;}function
getIndexes($table){throw
new
NotImplementedException;}function
getForeignKeys($table){throw
new
NotImplementedException;}}class
DibiSqliteReflector
extends
DibiObject
implements
IDibiReflector{private$driver;function
__construct(IDibiDriver$driver){$this->driver=$driver;}function
getTables(){$res=$this->driver->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view')
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view')
			ORDER BY name
		");$tables=array();while($row=$res->fetch(TRUE)){$tables[]=$row;}return$tables;}function
getColumns($table){$meta=$this->driver->query("
			SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '$table'
			UNION ALL
			SELECT sql FROM sqlite_temp_master WHERE type = 'table' AND name = '$table'")->fetch(TRUE);$res=$this->driver->query("PRAGMA table_info([$table])");$columns=array();while($row=$res->fetch(TRUE)){$column=$row['name'];$pattern="/(\"$column\"|\[$column\]|$column)\s+[^,]+\s+PRIMARY\s+KEY\s+AUTOINCREMENT/Ui";$type=explode('(',$row['type']);$columns[]=array('name'=>$column,'table'=>$table,'fullname'=>"$table.$column",'nativetype'=>strtoupper($type[0]),'size'=>isset($type[1])?(int)$type[1]:NULL,'nullable'=>$row['notnull']=='0','default'=>$row['dflt_value'],'autoincrement'=>(bool)preg_match($pattern,$meta['sql']),'vendor'=>$row);}return$columns;}function
getIndexes($table){$res=$this->driver->query("PRAGMA index_list([$table])");$indexes=array();while($row=$res->fetch(TRUE)){$indexes[$row['name']]['name']=$row['name'];$indexes[$row['name']]['unique']=(bool)$row['unique'];}foreach($indexes
as$index=>$values){$res=$this->driver->query("PRAGMA index_info([$index])");while($row=$res->fetch(TRUE)){$indexes[$index]['columns'][$row['seqno']]=$row['name'];}}$columns=$this->getColumns($table);foreach($indexes
as$index=>$values){$column=$indexes[$index]['columns'][0];$primary=FALSE;foreach($columns
as$info){if($column==$info['name']){$primary=$info['vendor']['pk'];break;}}$indexes[$index]['primary']=(bool)$primary;}if(!$indexes){foreach($columns
as$column){if($column['vendor']['pk']){$indexes[]=array('name'=>'ROWID','unique'=>TRUE,'primary'=>TRUE,'columns'=>array($column['name']));break;}}}return
array_values($indexes);}function
getForeignKeys($table){if(!($this->driver
instanceof
DibiSqlite3Driver)){}$res=$this->driver->query("PRAGMA foreign_key_list([$table])");$keys=array();while($row=$res->fetch(TRUE)){$keys[$row['id']]['name']=$row['id'];$keys[$row['id']]['local'][$row['seq']]=$row['from'];$keys[$row['id']]['table']=$row['table'];$keys[$row['id']]['foreign'][$row['seq']]=$row['to'];$keys[$row['id']]['onDelete']=$row['on_delete'];$keys[$row['id']]['onUpdate']=$row['on_update'];if($keys[$row['id']]['foreign'][0]==NULL){$keys[$row['id']]['foreign']=NULL;}}return
array_values($keys);}}class
DibiPdoDriver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver{private$connection;private$resultSet;private$affectedRows=FALSE;private$driverName;function
__construct(){if(!extension_loaded('pdo')){throw
new
NotSupportedException("PHP extension 'pdo' is not loaded.");}}function
connect(array&$config){$foo=&$config['dsn'];$foo=&$config['options'];DibiConnection::alias($config,'resource','pdo');if($config['resource']instanceof
PDO){$this->connection=$config['resource'];}else
try{$this->connection=new
PDO($config['dsn'],$config['username'],$config['password'],$config['options']);}catch(PDOException$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}if(!$this->connection){throw
new
DibiDriverException('Connecting error.');}$this->driverName=$this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);}function
disconnect(){$this->connection=NULL;}function
query($sql){$cmd=strtoupper(substr(ltrim($sql),0,6));static$list=array('UPDATE'=>1,'DELETE'=>1,'INSERT'=>1,'REPLAC'=>1);$this->affectedRows=FALSE;if(isset($list[$cmd])){$this->affectedRows=$this->connection->exec($sql);if($this->affectedRows===FALSE){$err=$this->connection->errorInfo();throw
new
DibiDriverException("SQLSTATE[$err[0]]: $err[2]",$err[1],$sql);}}else{$res=$this->connection->query($sql);if($res===FALSE){$err=$this->connection->errorInfo();throw
new
DibiDriverException("SQLSTATE[$err[0]]: $err[2]",$err[1],$sql);}else{return$this->createResultDriver($res);}}}function
getAffectedRows(){return$this->affectedRows;}function
getInsertId($sequence){return$this->connection->lastInsertId();}function
begin($savepoint=NULL){if(!$this->connection->beginTransaction()){$err=$this->connection->errorInfo();throw
new
DibiDriverException("SQLSTATE[$err[0]]: $err[2]",$err[1]);}}function
commit($savepoint=NULL){if(!$this->connection->commit()){$err=$this->connection->errorInfo();throw
new
DibiDriverException("SQLSTATE[$err[0]]: $err[2]",$err[1]);}}function
rollback($savepoint=NULL){if(!$this->connection->rollBack()){$err=$this->connection->errorInfo();throw
new
DibiDriverException("SQLSTATE[$err[0]]: $err[2]",$err[1]);}}function
getResource(){return$this->connection;}function
getReflector(){switch($this->driverName){case'mysql':return
new
DibiMySqlReflector($this);case'sqlite':case'sqlite2':return
new
DibiSqliteReflector($this);default:throw
new
NotSupportedException;}}function
createResultDriver(PDOStatement$resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:return$this->connection->quote($value,PDO::PARAM_STR);case
dibi::BINARY:return$this->connection->quote($value,PDO::PARAM_LOB);case
dibi::IDENTIFIER:switch($this->driverName){case'mysql':return'`'.str_replace('`','``',$value).'`';case'pgsql':return'"'.str_replace('"','""',$value).'"';case'sqlite':case'sqlite2':return'['.strtr($value,'[]','  ').']';case'odbc':case'oci':case'mssql':return'['.str_replace(array('[',']'),array('[[',']]'),$value).']';default:return$value;}case
dibi::BOOL:return$this->connection->quote($value,PDO::PARAM_BOOL);case
dibi::DATE:return$value
instanceof
DateTime?$value->format("'Y-m-d'"):date("'Y-m-d'",$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format("'Y-m-d H:i:s'"):date("'Y-m-d H:i:s'",$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){throw
new
NotImplementedException;}function
unescape($value,$type){if($type===dibi::BINARY){return$value;}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;switch($this->driverName){case'mysql':$sql.=' LIMIT '.($limit<0?'18446744073709551615':(int)$limit).($offset>0?' OFFSET '.(int)$offset:'');break;case'pgsql':if($limit>=0)$sql.=' LIMIT '.(int)$limit;if($offset>0)$sql.=' OFFSET '.(int)$offset;break;case'sqlite':case'sqlite2':$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');break;case'oci':if($offset>0){$sql='SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM ('.$sql.') t '.($limit>=0?'WHERE ROWNUM <= '.((int)$offset+(int)$limit):'').') WHERE "__rnum" > '.(int)$offset;}elseif($limit>=0){$sql='SELECT * FROM ('.$sql.') WHERE ROWNUM <= '.(int)$limit;}break;case'odbc':case'mssql':if($offset<1){$sql='SELECT TOP '.(int)$limit.' * FROM ('.$sql.')';break;}default:throw
new
NotSupportedException('PDO or driver does not support applying limit or offset.');}}function
getRowCount(){return$this->resultSet->rowCount();}function
fetch($assoc){return$this->resultSet->fetch($assoc?PDO::FETCH_ASSOC:PDO::FETCH_NUM);}function
seek($row){throw
new
NotSupportedException('Cannot seek an unbuffered result set.');}function
free(){$this->resultSet=NULL;}function
getResultColumns(){$count=$this->resultSet->columnCount();$columns=array();for($i=0;$i<$count;$i++){$row=@$this->resultSet->getColumnMeta($i);if($row===FALSE){throw
new
NotSupportedException('Driver does not support meta data.');}$row['table']=isset($row['table'])?$row['table']:NULL;$columns[]=array('name'=>$row['name'],'table'=>$row['table'],'nativetype'=>$row['native_type'],'fullname'=>$row['table']?$row['table'].'.'.$row['name']:$row['name'],'vendor'=>$row);}return$columns;}function
getResultResource(){return$this->resultSet;}}class
DibiPostgreDriver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver,IDibiReflector{private$connection;private$resultSet;private$affectedRows=FALSE;private$escMethod=FALSE;function
__construct(){if(!extension_loaded('pgsql')){throw
new
NotSupportedException("PHP extension 'pgsql' is not loaded.");}}function
connect(array&$config){if(isset($config['resource'])){$this->connection=$config['resource'];}else{if(!isset($config['charset']))$config['charset']='utf8';if(isset($config['string'])){$string=$config['string'];}else{$string='';DibiConnection::alias($config,'user','username');DibiConnection::alias($config,'dbname','database');foreach(array('host','hostaddr','port','dbname','user','password','connect_timeout','options','sslmode','service')as$key){if(isset($config[$key]))$string.=$key.'='.$config[$key].' ';}}DibiDriverException::tryError();if(empty($config['persistent'])){$this->connection=pg_connect($string,PGSQL_CONNECT_FORCE_NEW);}else{$this->connection=pg_pconnect($string,PGSQL_CONNECT_FORCE_NEW);}if(DibiDriverException::catchError($msg)){throw
new
DibiDriverException($msg,0);}}if(!is_resource($this->connection)){throw
new
DibiDriverException('Connecting error.');}if(isset($config['charset'])){DibiDriverException::tryError();pg_set_client_encoding($this->connection,$config['charset']);if(DibiDriverException::catchError($msg)){throw
new
DibiDriverException($msg,0);}}if(isset($config['schema'])){$this->query('SET search_path TO "'.$config['schema'].'"');}$this->escMethod=version_compare(PHP_VERSION,'5.2.0','>=');}function
disconnect(){pg_close($this->connection);}function
query($sql){$this->affectedRows=FALSE;$res=@pg_query($this->connection,$sql);if($res===FALSE){throw
new
DibiDriverException(pg_last_error($this->connection),0,$sql);}elseif(is_resource($res)){$this->affectedRows=pg_affected_rows($res);if(pg_num_fields($res)){return$this->createResultDriver($res);}}}function
getAffectedRows(){return$this->affectedRows;}function
getInsertId($sequence){if($sequence===NULL){$res=$this->query("SELECT LASTVAL()");}else{$res=$this->query("SELECT CURRVAL('$sequence')");}if(!$res)return
FALSE;$row=$res->fetch(FALSE);return
is_array($row)?$row[0]:FALSE;}function
begin($savepoint=NULL){$this->query($savepoint?"SAVEPOINT $savepoint":'START TRANSACTION');}function
commit($savepoint=NULL){$this->query($savepoint?"RELEASE SAVEPOINT $savepoint":'COMMIT');}function
rollback($savepoint=NULL){$this->query($savepoint?"ROLLBACK TO SAVEPOINT $savepoint":'ROLLBACK');}function
inTransaction(){return!in_array(pg_transaction_status($this->connection),array(PGSQL_TRANSACTION_UNKNOWN,PGSQL_TRANSACTION_IDLE),TRUE);}function
getResource(){return$this->connection;}function
getReflector(){return$this;}function
createResultDriver($resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:if($this->escMethod){return"'".pg_escape_string($this->connection,$value)."'";}else{return"'".pg_escape_string($value)."'";}case
dibi::BINARY:if($this->escMethod){return"'".pg_escape_bytea($this->connection,$value)."'";}else{return"'".pg_escape_bytea($value)."'";}case
dibi::IDENTIFIER:return'"'.str_replace('"','""',$value).'"';case
dibi::BOOL:return$value?'TRUE':'FALSE';case
dibi::DATE:return$value
instanceof
DateTime?$value->format("'Y-m-d'"):date("'Y-m-d'",$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format("'Y-m-d H:i:s'"):date("'Y-m-d H:i:s'",$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){throw
new
NotImplementedException;}function
unescape($value,$type){if($type===dibi::BINARY){return
pg_unescape_bytea($value);}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit>=0)$sql.=' LIMIT '.(int)$limit;if($offset>0)$sql.=' OFFSET '.(int)$offset;}function
__destruct(){$this->resultSet&&@$this->free();}function
getRowCount(){return
pg_num_rows($this->resultSet);}function
fetch($assoc){return
pg_fetch_array($this->resultSet,NULL,$assoc?PGSQL_ASSOC:PGSQL_NUM);}function
seek($row){return
pg_result_seek($this->resultSet,$row);}function
free(){pg_free_result($this->resultSet);$this->resultSet=NULL;}function
getResultColumns(){$hasTable=version_compare(PHP_VERSION,'5.2.0','>=');$count=pg_num_fields($this->resultSet);$columns=array();for($i=0;$i<$count;$i++){$row=array('name'=>pg_field_name($this->resultSet,$i),'table'=>$hasTable?pg_field_table($this->resultSet,$i):NULL,'nativetype'=>pg_field_type($this->resultSet,$i));$row['fullname']=$row['table']?$row['table'].'.'.$row['name']:$row['name'];$columns[]=$row;}return$columns;}function
getResultResource(){return$this->resultSet;}function
getTables(){$version=pg_version($this->connection);if($version['server']<8){throw
new
DibiDriverException('Reflection requires PostgreSQL 8.');}$res=$this->query("
			SELECT table_name as name, CAST(table_type = 'VIEW' AS INTEGER) as view
			FROM information_schema.tables
			WHERE table_schema = current_schema()
		");$tables=pg_fetch_all($res->resultSet);return$tables?$tables:array();}function
getColumns($table){$_table=$this->escape($table,dibi::TEXT);$res=$this->query("
			SELECT indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid AND pg_index.indisprimary
			WHERE pg_class.relname = $_table
		");$primary=(int)pg_fetch_object($res->resultSet)->indkey;$res=$this->query("
			SELECT *
			FROM information_schema.columns
			WHERE table_name = $_table AND table_schema = current_schema()
			ORDER BY ordinal_position
		");$columns=array();while($row=$res->fetch(TRUE)){$size=(int)max($row['character_maximum_length'],$row['numeric_precision']);$columns[]=array('name'=>$row['column_name'],'table'=>$table,'nativetype'=>strtoupper($row['udt_name']),'size'=>$size?$size:NULL,'nullable'=>$row['is_nullable']==='YES','default'=>$row['column_default'],'autoincrement'=>(int)$row['ordinal_position']===$primary&&substr($row['column_default'],0,7)==='nextval','vendor'=>$row);}return$columns;}function
getIndexes($table){$_table=$this->escape($table,dibi::TEXT);$res=$this->query("
			SELECT ordinal_position, column_name
			FROM information_schema.columns
			WHERE table_name = $_table AND table_schema = current_schema()
			ORDER BY ordinal_position
		");$columns=array();while($row=$res->fetch(TRUE)){$columns[$row['ordinal_position']]=$row['column_name'];}$res=$this->query("
			SELECT pg_class2.relname, indisunique, indisprimary, indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid
			INNER JOIN pg_class as pg_class2 on pg_class2.oid = pg_index.indexrelid
			WHERE pg_class.relname = $_table
		");$indexes=array();while($row=$res->fetch(TRUE)){$indexes[$row['relname']]['name']=$row['relname'];$indexes[$row['relname']]['unique']=$row['indisunique']==='t';$indexes[$row['relname']]['primary']=$row['indisprimary']==='t';foreach(explode(' ',$row['indkey'])as$index){$indexes[$row['relname']]['columns'][]=$columns[$index];}}return
array_values($indexes);}function
getForeignKeys($table){throw
new
NotImplementedException;}}class
DibiSqliteDriver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver{private$connection;private$resultSet;private$buffered;private$fmtDate,$fmtDateTime;private$dbcharset,$charset;function
__construct(){if(!extension_loaded('sqlite')){throw
new
NotSupportedException("PHP extension 'sqlite' is not loaded.");}}function
connect(array&$config){DibiConnection::alias($config,'database','file');$this->fmtDate=isset($config['formatDate'])?$config['formatDate']:'U';$this->fmtDateTime=isset($config['formatDateTime'])?$config['formatDateTime']:'U';$errorMsg='';if(isset($config['resource'])){$this->connection=$config['resource'];}elseif(empty($config['persistent'])){$this->connection=@sqlite_open($config['database'],0666,$errorMsg);}else{$this->connection=@sqlite_popen($config['database'],0666,$errorMsg);}if(!$this->connection){throw
new
DibiDriverException($errorMsg);}$this->buffered=empty($config['unbuffered']);$this->dbcharset=empty($config['dbcharset'])?'UTF-8':$config['dbcharset'];$this->charset=empty($config['charset'])?'UTF-8':$config['charset'];if(strcasecmp($this->dbcharset,$this->charset)===0){$this->dbcharset=$this->charset=NULL;}}function
disconnect(){sqlite_close($this->connection);}function
query($sql){if($this->dbcharset!==NULL){$sql=iconv($this->charset,$this->dbcharset.'//IGNORE',$sql);}DibiDriverException::tryError();if($this->buffered){$res=sqlite_query($this->connection,$sql);}else{$res=sqlite_unbuffered_query($this->connection,$sql);}if(DibiDriverException::catchError($msg)){throw
new
DibiDriverException($msg,sqlite_last_error($this->connection),$sql);}elseif(is_resource($res)){return$this->createResultDriver($res);}}function
getAffectedRows(){return
sqlite_changes($this->connection);}function
getInsertId($sequence){return
sqlite_last_insert_rowid($this->connection);}function
begin($savepoint=NULL){$this->query('BEGIN');}function
commit($savepoint=NULL){$this->query('COMMIT');}function
rollback($savepoint=NULL){$this->query('ROLLBACK');}function
getResource(){return$this->connection;}function
getReflector(){return
new
DibiSqliteReflector($this);}function
createResultDriver($resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:case
dibi::BINARY:return"'".sqlite_escape_string($value)."'";case
dibi::IDENTIFIER:return'['.strtr($value,'[]','  ').']';case
dibi::BOOL:return$value?1:0;case
dibi::DATE:return$value
instanceof
DateTime?$value->format($this->fmtDate):date($this->fmtDate,$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format($this->fmtDateTime):date($this->fmtDateTime,$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){throw
new
NotSupportedException;}function
unescape($value,$type){if($type===dibi::BINARY){return$value;}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}function
getRowCount(){if(!$this->buffered){throw
new
NotSupportedException('Row count is not available for unbuffered queries.');}return
sqlite_num_rows($this->resultSet);}function
fetch($assoc){$row=sqlite_fetch_array($this->resultSet,$assoc?SQLITE_ASSOC:SQLITE_NUM);$charset=$this->charset===NULL?NULL:$this->charset.'//TRANSLIT';if($row&&($assoc||$charset)){$tmp=array();foreach($row
as$k=>$v){if($charset!==NULL&&is_string($v)){$v=iconv($this->dbcharset,$charset,$v);}$tmp[str_replace(array('[',']'),'',$k)]=$v;}return$tmp;}return$row;}function
seek($row){if(!$this->buffered){throw
new
NotSupportedException('Cannot seek an unbuffered result set.');}return
sqlite_seek($this->resultSet,$row);}function
free(){$this->resultSet=NULL;}function
getResultColumns(){$count=sqlite_num_fields($this->resultSet);$columns=array();for($i=0;$i<$count;$i++){$name=str_replace(array('[',']'),'',sqlite_field_name($this->resultSet,$i));$pair=explode('.',$name);$columns[]=array('name'=>isset($pair[1])?$pair[1]:$pair[0],'table'=>isset($pair[1])?$pair[0]:NULL,'fullname'=>$name,'nativetype'=>NULL);}return$columns;}function
getResultResource(){return$this->resultSet;}function
registerFunction($name,$callback,$numArgs=-1){sqlite_create_function($this->connection,$name,$callback,$numArgs);}function
registerAggregateFunction($name,$rowCallback,$agrCallback,$numArgs=-1){sqlite_create_aggregate($this->connection,$name,$rowCallback,$agrCallback,$numArgs);}}class
DibiSqlite3Driver
extends
DibiObject
implements
IDibiDriver,IDibiResultDriver{private$connection;private$resultSet;private$fmtDate,$fmtDateTime;private$dbcharset,$charset;function
__construct(){if(!extension_loaded('sqlite3')){throw
new
NotSupportedException("PHP extension 'sqlite3' is not loaded.");}}function
connect(array&$config){DibiConnection::alias($config,'database','file');$this->fmtDate=isset($config['formatDate'])?$config['formatDate']:'U';$this->fmtDateTime=isset($config['formatDateTime'])?$config['formatDateTime']:'U';if(isset($config['resource'])&&$config['resource']instanceof
SQLite3){$this->connection=$config['resource'];}else
try{$this->connection=new
SQLite3($config['database']);}catch(Exception$e){throw
new
DibiDriverException($e->getMessage(),$e->getCode());}$this->dbcharset=empty($config['dbcharset'])?'UTF-8':$config['dbcharset'];$this->charset=empty($config['charset'])?'UTF-8':$config['charset'];if(strcasecmp($this->dbcharset,$this->charset)===0){$this->dbcharset=$this->charset=NULL;}$version=SQLite3::version();if($version['versionNumber']>='3006019'){$this->query("PRAGMA foreign_keys = ON");}}function
disconnect(){$this->connection->close();}function
query($sql){if($this->dbcharset!==NULL){$sql=iconv($this->charset,$this->dbcharset.'//IGNORE',$sql);}$res=@$this->connection->query($sql);if($this->connection->lastErrorCode()){throw
new
DibiDriverException($this->connection->lastErrorMsg(),$this->connection->lastErrorCode(),$sql);}elseif($res
instanceof
SQLite3Result){return$this->createResultDriver($res);}}function
getAffectedRows(){return$this->connection->changes();}function
getInsertId($sequence){return$this->connection->lastInsertRowID();}function
begin($savepoint=NULL){$this->query($savepoint?"SAVEPOINT $savepoint":'BEGIN');}function
commit($savepoint=NULL){$this->query($savepoint?"RELEASE SAVEPOINT $savepoint":'COMMIT');}function
rollback($savepoint=NULL){$this->query($savepoint?"ROLLBACK TO SAVEPOINT $savepoint":'ROLLBACK');}function
getResource(){return$this->connection;}function
getReflector(){return
new
DibiSqliteReflector($this);}function
createResultDriver(SQLite3Result$resource){$res=clone$this;$res->resultSet=$resource;return$res;}function
escape($value,$type){switch($type){case
dibi::TEXT:return"'".$this->connection->escapeString($value)."'";case
dibi::BINARY:return"X'".bin2hex((string)$value)."'";case
dibi::IDENTIFIER:return'['.strtr($value,'[]','  ').']';case
dibi::BOOL:return$value?1:0;case
dibi::DATE:return$value
instanceof
DateTime?$value->format($this->fmtDate):date($this->fmtDate,$value);case
dibi::DATETIME:return$value
instanceof
DateTime?$value->format($this->fmtDateTime):date($this->fmtDateTime,$value);default:throw
new
InvalidArgumentException('Unsupported type.');}}function
escapeLike($value,$pos){$value=addcslashes($this->connection->escapeString($value),'%_\\');return($pos<=0?"'%":"'").$value.($pos>=0?"%'":"'")." ESCAPE '\\'";}function
unescape($value,$type){if($type===dibi::BINARY){return$value;}throw
new
InvalidArgumentException('Unsupported type.');}function
applyLimit(&$sql,$limit,$offset){if($limit<0&&$offset<1)return;$sql.=' LIMIT '.$limit.($offset>0?' OFFSET '.(int)$offset:'');}function
__destruct(){$this->resultSet&&@$this->free();}function
getRowCount(){throw
new
NotSupportedException('Row count is not available for unbuffered queries.');}function
fetch($assoc){$row=$this->resultSet->fetchArray($assoc?SQLITE3_ASSOC:SQLITE3_NUM);$charset=$this->charset===NULL?NULL:$this->charset.'//TRANSLIT';if($row&&($assoc||$charset)){$tmp=array();foreach($row
as$k=>$v){if($charset!==NULL&&is_string($v)){$v=iconv($this->dbcharset,$charset,$v);}$tmp[str_replace(array('[',']'),'',$k)]=$v;}return$tmp;}return$row;}function
seek($row){throw
new
NotSupportedException('Cannot seek an unbuffered result set.');}function
free(){$this->resultSet->finalize();$this->resultSet=NULL;}function
getResultColumns(){$count=$this->resultSet->numColumns();$columns=array();static$types=array(SQLITE3_INTEGER=>'int',SQLITE3_FLOAT=>'float',SQLITE3_TEXT=>'text',SQLITE3_BLOB=>'blob',SQLITE3_NULL=>'null');for($i=0;$i<$count;$i++){$columns[]=array('name'=>$this->resultSet->columnName($i),'table'=>NULL,'fullname'=>$this->resultSet->columnName($i),'nativetype'=>$types[$this->resultSet->columnType($i)]);}return$columns;}function
getResultResource(){return$this->resultSet;}function
registerFunction($name,$callback,$numArgs=-1){$this->connection->createFunction($name,$callback,$numArgs);}function
registerAggregateFunction($name,$rowCallback,$agrCallback,$numArgs=-1){$this->connection->createAggregate($name,$rowCallback,$agrCallback,$numArgs);}}