<?php

/**
 * Very basic `mix test` unit test engine wrapper.
 *
 * It requires the use of the junit_formatter to produce the xml report.
 * Refer to https://hex.pm/packages/junit_formatter
 *
 * Cover reporting is not supported yet.
 *
 * When using mix configurations you likely want to execute the
 * unit tests with the correct environment, e.g. `MIX_ENV=test arc unit`
 *
 * Since 'mix test' requires to be run with a Mix project directory, the engine
 * scans all included paths backwards until it either finds a Mix project
 * directory or the arc project root directory. Therefore, a useful `.arcunit`
 * entry might look like this:
 *
 * {
 *   "engines": {
 *     "exunit": {
 *       "type": "exunit",
 *       "include": [
 *         "(^somemixproject/.+_test\\.ex[s]?$)"
 *       ]
 *     }
 *   }
 * }
 *
 */
final class ExunitTestEngine extends ArcanistUnitTestEngine {

  private $projectRoot;
  // this is the standard report location when using mix test
  private $junitFilename = '/_build/test/test-junit-report.xml';

  public function getEngineConfigurationName() {
    return 'exunit';
  }

  protected function supportsRunAllTests() {
    return true;
  }

  public function run() {
    $working_copy = $this->getWorkingCopy();
    $this->projectRoot = $working_copy->getProjectRoot();
    $projects = $this->findProjects($this->getPaths());
    $results = array();

    foreach ($projects as $project => $paths) {
      $junit_file = $project.$this->junitFilename;
      $future = $this->buildTestFuture($project, $paths, $junit_file);
      list($err, $stdout, $stderr) = $future->resolve();
      if (!Filesystem::pathExists($junit_file)) {
        throw new CommandException(
          pht('Command failed with error #%s!', $err),
        $future->getCommand(),
        $err,
        $stdout,
        $stderr);
      }
      $results = array_merge($results, $this->parseTestResults($junit_file));
    }

    return  $results;
  }

  private function buildTestFuture($project, $paths, $junit_file) {
    $cmd_line = csprintf('cd %s && mix test %Ls --junit',
      $project, $paths);

    return new ExecFuture('%C', $cmd_line);
  }

  public function parseTestResults($junit_file) {
    $parser = new ArcanistXUnitTestResultParser();
    $results = $parser->parseTestResults(
      Filesystem::readFile($junit_file));

    return $results;
  }

  public function shouldEchoTestResults() {
    return !$this->renderer;
  }

  private function findProjects($paths) {
    $projects = array();
    foreach ($paths as $path) {
      // FIXME: temporary workaround to handle weird file caching issue
      if (!file_exists($path)) {
        continue;
      }
      $path = realpath($path);
      $orig_path = $path;
      do {
        $path = dirname($path);
        foreach (glob($path.'/mix.exs') as $project_file) {
          $project = dirname($project_file);
          $projects_new = array($project => array($orig_path));
          $projects = array_merge_recursive($projects, $projects_new);
        }
      } while ($this->projectRoot != $path);
    }
    return $projects;
  }

}
