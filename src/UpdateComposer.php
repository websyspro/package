<?php

namespace Websyspro\Package;

class UpdateComposer
{
  private string $composerFile;
  private array $composerJson;
  private string $currentVersion;
  private string $newVersion;

  public function __construct(
    public string $directoryBase
  ){
    $this->composerFile = rtrim($directoryBase, "/\\") . "/composer.json";
    $this->load();
    $this->incrementPatch();
    $this->save();
    $this->gitRelease();
  }

  private function load(): void
  {
    if (!file_exists($this->composerFile)) {
      echo "composer.json not found at: {$this->composerFile}" . PHP_EOL;
      exit(1);
    }

    $content = file_get_contents($this->composerFile);
    $this->composerJson = json_decode($content, true);
    $this->currentVersion = $this->composerJson["version"] ?? "1.0.0";
  }

  private function incrementPatch(): void
  {
    $parts = explode(".", $this->currentVersion);

    while (count($parts) < 3) {
      $parts[] = "0";
    }

    $parts[2] = (int)$parts[2] + 1;

    $this->newVersion = implode(".", $parts);
    $this->composerJson["version"] = $this->newVersion;
  }

  private function save(): void
  {
    $content = json_encode($this->composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($this->composerFile, $content . PHP_EOL);
  }

  private function run(string $command): void
  {
    echo "> {$command}" . PHP_EOL;
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
      echo "Command failed with exit code {$exitCode}" . PHP_EOL;
      exit($exitCode);
    }
  }

  private function gitRelease(): void
  {
    $version = $this->newVersion;
    $tag = "v{$version}";

    echo PHP_EOL . "Releasing {$tag}..." . PHP_EOL . PHP_EOL;

    $this->run("git -C \"{$this->directoryBase}\" add .");
    $this->run("git -C \"{$this->directoryBase}\" commit -m \"Release {$tag}\"");
    $this->run("git -C \"{$this->directoryBase}\" tag {$tag}");
    $this->run("git -C \"{$this->directoryBase}\" push origin HEAD");
    $this->run("git -C \"{$this->directoryBase}\" push origin {$tag}");

    echo PHP_EOL . "Released {$tag} successfully!" . PHP_EOL;
  }

  public function getVersion(): string
  {
    return $this->newVersion;
  }
}
