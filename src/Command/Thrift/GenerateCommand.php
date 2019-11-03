<?php

namespace W7\ThriftRpc\Command\Thrift;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use W7\Console\Command\CommandAbstract;
use W7\Core\Exception\CommandException;

class GenerateCommand extends CommandAbstract
{
	protected function configure()
	{
		$this->addOption('--name', null, InputOption::VALUE_OPTIONAL, 'thrift file name');
	}

	protected function handle($options)
	{
		if (empty($options['name'])) {
			throw new CommandException('option name error');
		}

		$templateDir = BASE_PATH . '/thrift/';
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

	private function makeHandler($name, $path)
	{
		//生成handler
		$nameInfo = explode('/', $name);
		$namespace = str_replace('/', '\\', $path);
		$className = end($nameInfo);
		$interfaceName = $className . 'If';
		reset($nameInfo);

		$content = <<<EOF
<?php

namespace $namespace;

class $className implements $interfaceName {

}
EOF;
		file_put_contents(APP_PATH . '/ThriftRpc/' . $path . '/' . $className . 'Handler.php', $content);
		$this->output->success('generate thrift ' . $name . 'handler success');
	}
}