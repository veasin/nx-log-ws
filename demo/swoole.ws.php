<?php
/*
curl 'http://127.0.0.1:9501/log' -X POST -d '{"a":1}'
*/
use Swoole\Table;
use Swoole\WebSocket\Server;

$port=[
	'production' => 10011,
	'test' => 10012,
	'development' => 10010,
];

//构建在线列表
$map = new Table(1024);
$map->column('match', Table::TYPE_STRING, 32);//role_name._.role_id
$map->create();
//构建ws服务器
$ws_port =$port[$_SERVER['ENV_MODE']] ?? 9501;
$ws = new Server("0.0.0.0", $ws_port);
$ws->on('request', function(\Swoole\Http\Request $request, \Swoole\Http\Response $response) use($ws){
	$method =strtolower($request->server['request_method']);
	$uri =$request->server['request_uri'];
	if('post'===$method && '/log'===$uri){
		foreach($ws->connections as $fd){
			if($ws->isEstablished($fd)) $ws->push($fd, $request->getContent());
		}
	}
	//$response->write("{$method}:{$uri}\n");
});
$ws->on('message', function(Server $server, \Swoole\WebSocket\Frame $frame){

});
$ws->start();