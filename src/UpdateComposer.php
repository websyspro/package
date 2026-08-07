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
    
    $this->terminal->eof();
    $this->terminal->bold("Package Version Manager")->eof();
    $this->terminal->eof();
    
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
    $this->terminal->cyan("→ Carregando composer.json...")->eof();
    
    if (file_exists($this->composerFile()) === false) {
      $this->terminal->error("composer.json não encontrado em: {$this->composerFile()}");
      exit(1);
    }

    $this->composerJson = json_decode(
      file_get_contents( $this->composerFile ), true
    );

    $this->currentVersion = $this->composerJson[ "version" ] ?? "1.0.0";
    
    $this->terminal->text("  Versão atual: ")->green($this->currentVersion)->eof();
  }

  private function incrementPatch(
  ): void {
    $this->terminal->cyan("→ Incrementando versão...")->eof();
    
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
    
    $this->terminal->text("  Nova versão: ")->green($this->newVersion)->eof();
  }

  private function save(
  ): void {
    $this->terminal->dim("→ Salvando composer.json...")->eof();
    
    $content = json_encode(
      $this->composerJson, 
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    $content = str_replace( "    ", "  ", $content );
    file_put_contents( $this->composerFile, $content . PHP_EOL );
    
    $this->terminal->success("Arquivo salvo");
  }

  private function run(
    string $command,
    bool $silent = true
  ): void {
    if ($silent) {
      // Redireciona stdout e stderr para null
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
  ): void {
    $version = $this->newVersion;
    $tag = "v{$version}";
    $packageName = $this->composerJson["name"] ?? "package";

    $this->terminal->eof();
    $this->terminal->cyan("{$packageName} ")->bold("Publish")->eof();
    $this->terminal->eof();

    // Etapa 1: Adicionando arquivos
    $this->terminal->spinner(0, "Adicionando arquivos");
    $this->run("git add .");
    $this->terminal->clearLine()->success("Adicionando arquivos")->eof();
    
    // Etapa 2: Criando commit
    $this->terminal->spinner(1, "Criando commit");
    $this->run("git commit -m \"Release {$tag}\"");
    $this->terminal->clearLine()->success("Criando commit")->eof();
    
    // Etapa 3: Criando tag
    $this->terminal->spinner(2, "Criando tag {$tag}");
    $this->run("git tag {$tag}");
    $this->terminal->clearLine()->success("Criando tag {$tag}")->eof();
    
    // Etapa 4: Enviando para origin
    $this->terminal->spinner(3, "Enviando para origin");
    $this->run("git push origin HEAD");
    $this->terminal->clearLine()->success("Enviando para origin")->eof();
    
    // Etapa 5: Enviando tag
    $this->terminal->spinner(0, "Enviando tag");
    $this->run("git push origin {$tag}");
    $this->terminal->clearLine()->success("Enviando tag")->eof();

    $this->terminal->eof();
    $this->terminal->bgGreen(" Publish finish {$tag} ")->eof();
    $this->terminal->eof();
  }

  public function getVersion(
  ): string {
    return $this->newVersion;
  }
}
