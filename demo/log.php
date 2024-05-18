#!/usr/bin/php
<?php
/**
 * 队列命令控制台，使用全路径运行(使用全路径检查脚本运行，防止重名)
 */
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');
/**
 * 运行模式
 */
$work_mode = 'development';
if(!isset($argv[1])) $argv[1] = '-d';
switch($argv[1]){
	case '-p':
		$work_mode = 'production';
		break;
	case '-t':
		$work_mode = 'test';
		break;
	case '-d':
		//$work_mode ='development';
		break;
	default:
		$argv[2] = $argv[1];
		break;
}
/**
 * 通过ps命令检查进程是否运行
 *
 * @param string $mode 运行模式
 * @param string $cmd  运行命令
 * @return array|bool
 */
function check(string $mode, string $cmd = 'start'): bool|array{
	global $argv;
	$cmd = 'ps -aux|grep -v "grep" | grep "' . $argv[0] . ' ' . $mode . ' ' . $cmd . '"';
	$result = shell_exec($cmd);
	if($result){
		$user = strtok($result, ' ');
		$pid = strtok(' ');
		$cpu = strtok(' ');
		$mem = strtok(' ');
		$vsz = strtok(' ');
		$rss = strtok(' ');
		$tty = strtok(' ');
		$stat = strtok(' ');
		$start = strtok(' ');
		return [$user, (int)$pid, $start];
	}
	else return false;
}

/**
 * 杀掉进程
 *
 * @param $pid
 * @return string
 */
function kill($pid): string{
	$cmd = "kill -9 {$pid}";
	return shell_exec($cmd);
}

/**
 * 进程僵尸化
 *
 * @return int
 */
function daemonize(): int{
	$pid = pcntl_fork();
	if($pid == 0) posix_setsid();//建立一个有别于终端的新session以脱离终端
	return $pid;
	//	if(posix_getppid()>1){//非独立而外部调用
	//		$pid=pcntl_fork();
	//		if($pid==-1) return false;//分进程出错
	//		elseif($pid>0) return true;//僵尸进程(主进程退出)
	//		posix_setsid();//建立一个有别于终端的新session以脱离终端
	//	} else $pid =0;//已经在子进程中
	//	return $pid;
}

/**
 * 处理运行命令
 */
if(!isset($argv[2])) $argv[2] = 'help';//默认为帮助命令
switch($argv[2]){
	case 'start':
		$r = check($argv[1]);
		$pid = getmypid();
		if(!$r || $r[1] == $pid){
			$pid = daemonize();
			if($pid == 0){
				$_SERVER['ENV_MODE'] = $work_mode;
				include(__DIR__ . '/swoole.ws.php');
			}
			else{
				echo "server \033[32;40m[running]\033[0m. start: $r[2]" . PHP_EOL;
				echo PHP_EOL;
			}
		}
		else{
			echo "server \033[32;40m[already running]\033[0m. user: $r[0] pid: $r[1] start: $r[2]" . PHP_EOL;
			echo PHP_EOL;
		}
		break;
	case 'status':
		$r = check($argv[1]);
		if($r){
			echo "server \033[32;40m[running]\033[0m. user: $r[0] pid: $r[1] start: $r[2]" . PHP_EOL;
		}
		else{
			echo "server \033[31;40m[stop]\033[0m." . PHP_EOL;
		}
		echo PHP_EOL;
		break;
	case 'stop':
		$r = check($argv[1]);
		if($r){
			echo "server \033[32;40m[running]\033[0m. user: $r[0] pid: $r[1] start: $r[2]" . PHP_EOL;
			kill($r[1]);
		}
		echo "server \033[31;40m[stop]\033[0m." . PHP_EOL;
		echo PHP_EOL;
		break;
	case 'check':
		$r = check($argv[1]);
		if(!$r){
			$pid = daemonize();
			if($pid == 0){
				shell_exec($argv[0] . ' ' . $argv[1] . ' start');
			}
			elseif($pid > 0){
				kill($pid);
				//echo $pid.PHP_EOL;
				echo "server \033[32;40m[running]\033[0m." . PHP_EOL;
				echo PHP_EOL;
			}
			else{
				echo "server \033[32;40m[error]\033[0m." . PHP_EOL;
				echo PHP_EOL;
			}
		}
		else{
			echo "server \033[32;40m[running]\033[0m. user: $r[0] pid: $r[1] start: $r[2]" . PHP_EOL;
			echo PHP_EOL;
		}
		break;
	case 'help':
	default:
		echo PHP_EOL;
		echo "php log.php [-d|-t|-p] [start|stop|status|check|help]" . PHP_EOL;
		echo "  -d: development" . PHP_EOL;
		echo "  -t: test" . PHP_EOL;
		echo "  -p: production" . PHP_EOL;
		echo PHP_EOL;
		break;
}


