<?php

final class ArcanistElixirLinter extends ArcanistExternalLinter {

  private $projectRootDir = null;

  public function getInfoName() {
    return 'Elixir';
  }

  public function getInfoURI() {
    return 'https://hexdocs.pm/mix/Mix.Tasks.Format.html';
  }

  public function getInfoDescription() {
    return pht('Formats Elixir code.');
  }

  public function getLinterName() {
    return 'ELIXIR';
  }

  public function getLinterConfigurationName() {
    return 'elixir';
  }
  public function getLinterConfigurationOptions() {
    $options = array(
      'elixir.project-root-dir' => array(
        'type' => 'optional string',
        'help' => pht(
          'Adjust the project root directory in which mix is executed.'.
          'This is useful in case the Elixir project\'s root'.
          'resides in a subdirectory of the repository.'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  public function setProjectRootDir($new_dir) {
    $this->projectRootDir = $new_dir;
    return $this;
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'elixir.project-root-dir':
        $this->setProjectRootDir($value);
        return;
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

  public function getDefaultBinary() {
    return 'mix';
  }

  protected function getMandatoryFlags() {
    $flags = array();
    if ($this->projectRootDir) {
      $flags[] = 'cmd';
      $flags[] = 'cd '.$this->projectRootDir;
      $flags[] = '&&';
      $flags[] = 'mix';
    }
    $flags[] = 'format';
    $flags[] = '--check-formatted';
    return $flags;
  }

  public function getInstallInstructions() {
    return pht(
      'Install Elixir at least v1.6.0 to use this linter.');
  }

  public function shouldExpectCommandErrors() {
    return true;
  }

  protected function canCustomizeLintSeverities() {
    return false;
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {
    $lines = phutil_split_lines($stdout, false);

    $messages = array();
    foreach ($lines as $line) {
      $matches = explode(':', $line, 5);

      if (count($matches) === 5) {
        $message = new ArcanistLintMessage();
        $message->setPath($path);
        $message->setLine($matches[1]);
        $message->setChar($matches[2]);
        $message->setCode($this->getLinterName());
        $message->setName($this->getLinterName());
        $message->setDescription(ucfirst(trim($matches[4])));
        $message->setSeverity($this->getSeverity(ucfirst(trim($matches[3]))));

        $messages[] = $message;
      }
    }

    return $messages;
  }

  private function getSeverity($identifier) {
    switch ($identifier) {
    case 'W':
      return ArcanistLintSeverity::SEVERITY_WARNING;
    case 'E':
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
    return ArcanistLintSeverity::SEVERITY_ADVICE;
  }
}
