<?php

namespace Lumite\Commands;

use Lumite\Console\BaseCommand;

class ClearViewCacheCommand extends BaseCommand
{
	protected string $signature = 'view:clear';
	protected string $description = 'Clear compiled Blade view cache';

	public function handle(): int
	{
		$cacheDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'views';
		$cleared = 0;
		if (is_dir($cacheDir)) {
			$files = glob($cacheDir . DIRECTORY_SEPARATOR . '*.php');
			if ($files) {
				foreach ($files as $file) {
					@unlink($file);
					$cleared++;
				}
			}
		}
		$this->line("Cleared {$cleared} compiled view file(s).");
		return self::SUCCESS;
	}
}

