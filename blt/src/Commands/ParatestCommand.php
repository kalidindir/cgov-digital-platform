<?php

namespace Acquia\Blt\Custom\Commands;

use Acquia\Blt\Robo\BltTasks;
use Acquia\Blt\Custom\ParatestTrait;
use Acquia\Blt\Robo\Exceptions\BltException;
use Robo\Contract\VerbosityThresholdInterface;

/**
 * Defines commands in the "tests" namespace.
 */
class ParatestCommand extends BltTasks {
  use ParatestTrait;

  /**
   * Directory in which test logs and reports are generated.
   *
   * @var string
   */
  protected $reportsDir;

  /**
   * The filename for Paratest report.
   *
   * @var string
   */
  protected $reportFile;

  /**
   * An array that contains config to override customize paratest command.
   *
   * @var array
   */
  protected $paratestConfig;

  /**
   * This hook will fire for all commands in this command file.
   *
   * @hook init
   */
  public function initialize() {
    $this->reportsDir = $this->getConfigValue('reports.localDir') . '/phpunit';
    $this->reportFile = $this->reportsDir . '/results.xml';
    $this->testsDir = $this->getConfigValue('repo.root') . '/tests/phpunit';
    $this->paratestConfig = $this->getConfigValue('paratest');
  }

  /**
   * Executes all Paratest (PHPUnit) tests.
   *
   * @usage
   *   Executes all configured tests.
   * @usage -D test.paths=${PWD}/tests/Functional/Examples.php
   *   Executes scenarios in the Examples.feature file.
   *
   * @command custom:paratest:run
   * @aliases cpr paratest custom:paratest
   */
  public function testsParatest() {
    $this->createLogs();
    $paratest_path = $this->getConfigValue('test.paths');
    foreach ($this->paratestConfig as $test) {
      $task = $this->taskParatest()
        ->xml($this->reportFile)
        ->printOutput(TRUE)
        ->printMetadata(FALSE);

      if (isset($test['class'])) {
        $task->arg($test['class']);
        if (isset($test['file'])) {
          $task->arg($test['file']);
        }
      }
      else {
        if (is_string($paratest_path)) {
          $task->arg($paratest_path);
        }
        elseif (isset($test['path'])) {
          $task->arg($test['path']);
        }
      }

      if (isset($test['path'])) {
        $task->dir($test['path']);
      }

      $supported_options = [
        'config' => 'configuration',
        'exclude-group' => 'exclude-group',
        'filter' => 'filter',
        'group' => 'group',
        'testsuite' => 'testsuite',
        'procs' => 'processes',
      ];

      foreach ($supported_options as $yml_key => $option) {
        if (isset($test[$yml_key])) {
          $task->option("--$option", $test[$yml_key]);
        }
      }

      $result = $task->run();
      $exit_code = $result->getExitCode();

      if ($exit_code) {
        throw new BltException("Paratest tests failed.");
      }
    }
  }

  /**
   * Creates empty log directory and log file for Paratest (PHPUnit) tests.
   */
  protected function createLogs() {
    $this->taskFilesystemStack()
      ->mkdir($this->reportsDir)
      ->touch($this->reportFile)
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
      ->run();
  }

}
