<?php

namespace nx\parts\log;

use nx\parts\log;

trait ws{
	use log;
	protected function nx_parts_log_ws(): \Generator{
		$this->nx_parts_log();
		$setup =$this['log/ws'] ?? [];
		if($setup['uri'] ?? false){
			$this->log->addWriter($this->log_ws_writer(...),
				$this->log_ws_name ?? 'default',
				true
			);
			yield;
			unset($this->log);
		}
	}
	protected function log_ws_writer($log): void{
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this['log/ws']['uri']);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
			'app'=>$this['app:uuid'],
			'logs'=>$log,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json;charset=UTF-8"]); // 设置 header
		curl_exec($ch);
	}
}