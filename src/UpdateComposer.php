<?php

namespace Websyspro\Package;

use function count;

class UpdateComposer
{
  private string $composerFile;
  private array $composerJson;
  private string $currentVersion;
  private string $newVersion;
  private Terminal $terminal;

  public function __construct(
    public string $directory
  ){
    $this->terminal = Terminal::init();
    $this->composerFile = rtrim( $directory, "/\\" ) . "/composer.json";
    
    $this->terminal
      ->eof()
      ->bold("Package Version Manager")
      ->eof();
    
    $this->load();
    $this->incrementPatch();
    $this->save();
    $this->gitRelease();
  }

  private function composerFile(
  ): string {
    return sprintf(
      "%s/%s", rtrim(
        $this->directory, "/\\"
      ), "composer.json"
    );
  }

  private function load(
  ): void {
    if (file_exists($this->composerFile()) === false) {
      $this->terminal->error("composer.json não encontrado em: {$this->composerFile()}");
      exit(1);
    }

    $this->composerJson = json_decode(
      file_get_contents( $this->composerFile ), true
    );

    $this->currentVersion = $this->composerJson[ "version" ] ?? "1.0.0";
  }

  private function incrementPatch(
  ): void {
    $parts = explode(
      ".", $this->currentVersion
    );

    while(count( $parts ) < 3){
      $parts[] = "0";
    }

    $parts[2] = (int)$parts[2] + 1;

    $this->newVersion = implode(
      ".", $parts
    );
    
    $this->composerJson["version"] = $this->newVersion;
  }

  private function save(
  ): void {
    // $this->terminal->dim("→ Salvando composer.json...")->eof();
    
    $content = json_encode(
      $this->composerJson, 
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    $content = str_replace( "    ", "  ", $content );
    file_put_contents( $this->composerFile, $content . PHP_EOL );
    
    // $this->terminal->success("Arquivo salvo");
  }

  private function runWithSpinner(
    string $command,
    string $message,
    bool $silent = true
  ): void {
    // Mostra spinner animado
    for ($i = 0; $i < 4; $i++) {
      $this->terminal->spinner($i, $message);
      usleep(100000); // 0.1 segundo
    }
    
    // Executa comando
    $this->run($command, $silent);
    
    // Substitui spinner por sucesso
    $this->terminal->clearLine()->success($message)->eof();
  }

  private function run(
    string $command,
    bool $silent = true
  ): void {
    if ($silent) {
      if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command .= " >nul 2>&1";
      } else {
        $command .= " >/dev/null 2>&1";
      }
    }
    
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
      $this->terminal->error("Comando falhou com código {$exitCode}");
      $this->terminal->dim("  $ {$command}")->eof();
      exit($exitCode);
    }
  }

  private function gitRelease(
    array $commands = []
  ): void {
    $version = $this->newVersion;
    $tag = "v{$version}";
    $packageName = $this->composerJson["name"] ?? "package";

    /* define header */
    $this->terminal
      ->eof()
      ->cyan("{$packageName} ")
      ->bold( "publish v{$this->newVersion}")
      ->eof()
      ->eof();

    /* define commands */
    $commands = [
      [ 
        "command" => "git add .",
        "context" => "createds file(s)"
      ],
      [
        "command" => "git commit -m \"Release {$this->newVersion}\"",
        "context" => "create commit"
      ],
      [ 
        "command" => "git tag {$this->newVersion}",
        "context" => "create tag"
      ],
      [
        "command" => "git push origin HEAD",
        "context" => "send to origin"
      ],
      [ 
        "command" => "git push origin {$this->newVersion}",
        "context" => "send to origin tag" 
      ]
    ];

    foreach( $commands as $key => $command ){
      $this->run( $command[ "command" ]);

      $this->terminal
        ->green( $command[ "context" ] )
        ->eof();
    }

    $this->terminal
      ->eof()
      ->cyan("Publish finish {$tag} ")
      ->eof();
  }

  public function getVersion(
  ): string {
    return $this->newVersion;
  }
}
