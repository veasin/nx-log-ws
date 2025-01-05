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
		$context=stream_context_create(['http'=>[
			'method'=>'POST',
			'header'=>["Content-Type: application/json;charset=UTF-8"],
			'content'=>json_encode([
				'app'=>$this['app:uuid'],
				'logs'=>$log,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		]]);
		fclose(fopen($this['log/ws']['uri'], 'r', false, $context));
	}
}