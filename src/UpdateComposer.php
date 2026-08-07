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
    $this->terminal->bold("═══════════════════════════════════════")->eof();
    $this->terminal->bold("    Package Version Manager")->eof();
    $this->terminal->bold("═══════════════════════════════════════")->eof();
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
    $this->terminal->dim("→ Carregando composer.json...")->eof();
    
    if (file_exists($this->composerFile()) === false) {
      $this->terminal->error("composer.json não encontrado em: {$this->composerFile()}");
      exit(1);
    }

    $this->composerJson = json_decode(
      file_get_contents( $this->composerFile ), true
    );

    $this->currentVersion = $this->composerJson[ "version" ] ?? "1.0.0";
    
    $this->terminal->cyan("  Versão atual: {$this->currentVersion}")->eof();
  }

  private function incrementPatch(
  ): void {
    $this->terminal->dim("→ Incrementando versão...")->eof();
    
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
    
    $this->terminal->green("  Nova versão: {$this->newVersion}")->eof();
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
    string $command
  ): void {
    $this->terminal->dim("  $ {$command}")->eof();
    passthru( $command, $exitCode );

    if( $exitCode !== 0 ){
      $this->terminal->error("Comando falhou com código {$exitCode}");
      exit($exitCode);
    }
  }

  private function gitRelease(
  ): void {
    $version = $this->newVersion;
    $tag = "v{$version}";
    $dir = rtrim($this->directory, "/\\");

    $this->terminal->eof();
    $this->terminal->bold("═══════════════════════════════════════")->eof();
    $this->terminal->yellow("  Release {$tag}")->eof();
    $this->terminal->bold("═══════════════════════════════════════")->eof();
    $this->terminal->eof();

    // Confirma o release
    if (!$this->terminal->confirm("Deseja fazer o release {$tag}?")) {
      $this->terminal->warning("Release cancelado pelo usuário");
      exit(0);
    }

    $this->terminal->eof();
    $this->terminal->dim("→ Adicionando arquivos ao Git...")->eof();
    $this->run("git -C \"{$dir}\" add .");
    
    $this->terminal->dim("→ Criando commit...")->eof();
    $this->run("git -C \"{$dir}\" commit -m \"Release {$tag}\"");
    
    $this->terminal->dim("→ Criando tag {$tag}...")->eof();
    $this->run("git -C \"{$dir}\" tag {$tag}");
    
    $this->terminal->dim("→ Enviando para origin...")->eof();
    $this->run("git -C \"{$dir}\" push origin HEAD");
    
    $this->terminal->dim("→ Enviando tag...")->eof();
    $this->run("git -C \"{$dir}\" push origin {$tag}");

    $this->terminal->eof();
    $this->terminal->bold("═══════════════════════════════════════")->eof();
    $this->terminal->success("Release {$tag} publicado com sucesso!");
    $this->terminal->bold("═══════════════════════════════════════")->eof();
    $this->terminal->eof();
  }

  public function getVersion(
  ): string {
    return $this->newVersion;
  }
}
