<?php

/**
 * Rangine thrift rpc server
 *
 * (c) We7Team 2019 <https://www.rangine.com>
 *
 * document http://s.w7.cc/index.php?c=wiki&do=view&id=317&list=2284
 *
 * visited https://www.rangine.com for more details
 */

namespace W7\ThriftRpc\Command\Thrift;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\Console\Command\CommandAbstract;
use W7\Core\Exception\CommandException;

class GenerateCommand extends CommandAbstract {
	protected function configure() {
		$this->addOption('--name', null, InputOption::VALUE_OPTIONAL, 'thrift file name');
	}

	protected function handle($options) {
		if (empty($options['name'])) {
			throw new CommandException('option name error');
		}

		$templateDir = BASE_PATH . '/thrift/';
		$options['name'] = ucfirst($options['name']);
		if (!file_exists($templateDir . $options['name'] . '.thrift')) {
			throw new CommandException('thrift ' . $options['name'] . ' not exists');
		}

		$thriftDir = $templateDir . $options['name'];
		exec('thrift -gen php:server ' . $thriftDir . '.thrift');

		$dir = BASE_PATH . '/gen-php';
		$files = Finder::create()
			->in($dir)
			->files()
			->ignoreDotFiles(true)
			->name('/^Types.php$/');
		if ($files->count() == 0) {
			throw new CommandException('generate thrift ' . $options['name'] . ' fail');
		}
		/**
		 * @var SplFileInfo $file
		 */
		foreach ($files as $file) {
			$path = $file->getRelativePath();
		}
		if (is_dir(APP_PATH . '/ThriftRpc/' . $path)) {
			throw new CommandException('thrift ' . $options['name'] . ' has exist');
		}

		$command = 'mkdir -p ' . APP_PATH . '/ThriftRpc/' . $path . ' && mv ./gen-php/' . $path . '/* ' . APP_PATH . '/ThriftRpc/' . $path . ' && rm ./gen-php/ -rf';
		exec($command);

		$this->output->success('generate thrift ' . $options['name'] . ' success');

		$this->makeHandler($options['name'], $path);
	}

	private function makeHandler($name, $path) {
		//生成handler
		$nameInfo = explode('/', $name);
		$namespace = str_replace('/', '\\', $path);
		$className = end($nameInfo);
		$interfaceName = $className . 'ServiceIf';
		$className .= 'Handler';
		reset($nameInfo);

		$content = <<<EOF
<?php

namespace $namespace;

class $className implements $interfaceName {

}
EOF;
		file_put_contents(APP_PATH . '/ThriftRpc/' . $path . '/' . $className . '.php', $content);
		$this->output->success('generate thrift ' . $name . 'handler success');
	}
}
