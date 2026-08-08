<?php

namespace Websyspro\Package;

use Websyspro\Package\Interfaces\CMDStructure;
use Websyspro\Package\Interfaces\PackageStructure;
use function sprintf;

class UpdateComposer
{
  private string $composerFile;
  private array $composerStructure;

  public function __construct(
    public string $directory
  ){
    $this->composerFile();
    $this->composerLoader();
    $this->composerSaveVersion();
    $this->composerSendVersion();



    // $this->save();
    // $this->gitRelease();
  }

  private function write(
    string $data
  ): void {
    fwrite( STDOUT, $data );
  }

  private function extractVersion(
  ): PackageStructure {
    return new PackageStructure(
      ...[
        $this->composerStructure[ ConstsComposer::name ],
        $this->composerStructure[ ConstsComposer::description ],
        ...explode( 
          ConstsComposer::versionSeparator, $this->composerStructure[
            ConstsComposer::version 
          ] ?? ConstsComposer::versionDefault
        )
      ]
    );
  }

  private function composerFile(
  ): void {
    $this->composerFile = sprintf(
      "%s/%s", rtrim(
        $this->directory, "/\\"
      ), "composer.json"
    );
  }

  private function composerLoader(
  ): void {
    if( file_exists( $this->composerFile) === false ){
      $this->write( "\033[31mComposer file not found\033[0m" );
    } else {
      $this->composerStructure = json_decode(
        file_get_contents( $this->composerFile ), true
      );
    }
  }

  private function composerSaveVersion(
  ): void {
    $this->composerStructure[
      ConstsComposer::version
    ] = $this->extractVersion()->inc();

    $content = json_encode( 
      $this->composerStructure, 
      JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    $content = str_replace( "    ", "  ", $content );
    file_put_contents( $this->composerFile, $content . PHP_EOL );
  }

  private function shellExec(
    CMDStructure $cmd
  ): void {
    exec(
      strtoupper( substr( PHP_OS, 0, 3 )) === "WIN"
        ? "{$cmd->script} >nul 2>&1" 
        : "{$cmd->script} >/dev/null 2>&1", 
      $output, $result_code
    );

    if( $result_code !== 0 ){
      exit( $result_code );
    } else {
      $this->write( "\r\033[32m{$cmd->hits}\033[0m" );
    }
  }

  private function composerSendVersion(
  ): void {
    $extractVersion = $this->extractVersion();


    $packageName = $this->composerJson["name"] 
      ?? "package";


    $this->write( "\n\033[1mPackage Version Manager\033[0m\n" );
    $this->write( "\n\033[36m{$packageName}\033[0m\033[1m{$extractVersion->get()}\033[0m\n\n\033[?25l" );

    /* define commands */
    foreach([
      new CMDStructure( "git add .", "add files" ),
      new CMDStructure( "git commit -m \"Release {$this->newVersion}\"", "create commit" ),
      new CMDStructure( "git tag {$this->newVersion}", "create tag" ),
      new CMDStructure( "git push origin HEAD", "send to origin" ),
      new CMDStructure( "git push origin {$this->newVersion}", "send to origin tag" )
    ] as $cmdStructure ){
      $this->shellExec( $cmdStructure );
    }

    $this->write( "\n\033[?25h\033[36mPublish finish {$this->newVersion}\033[0m\n" );
  }

  public function getVersion(
  ): string {
    return $this->newVersion;
  }
}
