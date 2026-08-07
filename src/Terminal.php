<?php

namespace Websyspro\Package;

use function in_array;

/**
 * Class Terminal
 * 
 * Versão simplificada para saída formatada no terminal
 * 
 * @package Websyspro\Package
 */
class Terminal
{
  /**
   * Escreve um valor no STDOUT
   */
  private function write(
    string $value,
    bool $flush = false
  ): static {
    fwrite(STDOUT, $value);

    if ($flush) {
      fflush(STDOUT);
    }

    return $this;
  }

  /**
   * Limpa a tela do terminal
   */
  public function clear(
  ): static {
    return $this->write(
      "\033[2J\033[H", true
    );
  }

  /**
   * Limpa a linha atual
   */
  public function clearLine(
  ): static {
    return $this->write(
      "\033[2K"
    );
  }

  /**
   * Aplica cor verde ao texto
   */
  public function green(
    string $text
  ): static {
    return $this->write(
      "\033[32m{$text}\033[0m"
    );
  }

  /**
   * Aplica cor vermelha ao texto
   */
  public function red(
    string $text
  ): static {
    return $this->write(
      "\033[31m{$text}\033[0m"
    );
  }

  /**
   * Aplica cor amarela ao texto
   */
  public function yellow(
    string $text
  ): static {
    return $this->write(
      "\033[33m{$text}\033[0m"
    );
  }

  /**
   * Aplica cor azul ao texto
   */
  public function blue(
    string $text
  ): static {
    return $this->write(
      "\033[34m{$text}\033[0m"
    );
  }

  /**
   * Aplica cor ciano ao texto
   */
  public function cyan(
    string $text
  ): static {
    return $this->write(
      "\033[36m{$text}\033[0m"
    );
  }

  /**
   * Aplica cor de fundo verde ao texto
   */
  public function bgGreen(
    string $text
  ): static {
    return $this->write("\033[42m{$text}\033[0m");
  }

  /**
   * Aplica formatação em negrito
   */
  public function bold(
    string $text
  ): static {
    return $this->write(
      "\033[1m{$text}\033[0m"
    );
  }

  /**
   * Aplica cor esmaecida (dim)
   */
  public function dim(
    string $text
  ): static {
    return $this->write(
      "\033[2m{$text}\033[0m"
    );
  }

  /**
   * Escreve texto simples
   */
  public function text(
    string $text,
    bool $flush = false
  ): static {
    return $this->write(
      $text, $flush
    );
  }

  /**
   * Escreve uma linha com quebra
   */
  public function line(
    string $text = "",
    bool $flush = false
  ): static {
    return $this->write(
      "{$text}\n", $flush
    );
  }

  /**
   * Adiciona quebra de linha
   */
  public function eof(
  ): static {
    return $this->write(
      "\n"
    );
  }

  /**
   * Exibe um spinner animado
   */
  public function spinner(
    int $frame = 0,
    string $text = ""
  ): static {
    $frames = ["|", "/", "-", "\\"];
    $char = $frames[$frame % 4];
    
    $output = $text ? "{$char} {$text}" : $char;
    
    return $this->write("\r{$output}", true);
  }

  /**
   * Exibe mensagem de sucesso
   */
  public function success(
    string $text
  ): static {
    return $this->green(
      "✓ {$text}"
    )->eof();
  }

  /**
   * Exibe mensagem de erro
   */
  public function error(
    string $text
  ): static {
    return $this->red(
      "✗ {$text}"
    )->eof();
  }

  /**
   * Exibe mensagem de aviso
   */
  public function warning(
    string $text
  ): static {
    return $this->yellow(
      "⚠ {$text}"
    )->eof();
  }

  /**
   * Exibe mensagem informativa
   */
  public function info(
    string $text
  ): static {
    return $this->cyan(
      "ℹ {$text}"
    )->eof();
  }

  /**
   * Solicita confirmação do usuário
   */
  public function confirm(
    string $question,
    bool $defaultYes = true
  ): bool {
    $options = $defaultYes ? "[S/n]" : "[s/N]";
    $this->write("{$question} {$options}: ");
    
    $response = strtolower(trim(fgets(STDIN)));
    
    if ($response === "") {
      return $defaultYes;
    }
    
    return in_array($response, ["s", "sim", "y", "yes"]);
  }

  /**
   * Exibe uma barra de progresso com informações detalhadas
   */
  public function progressBarDetailed(
    int $current,
    int $total,
    int $width = 40,
    string $prefix = "Progress"
  ): static {
    $percentage = ($current / $total) * 100;
    $filled = (int)(($current / $total) * $width);
    $empty = $width - $filled;
    
    $bar = str_repeat("█", $filled) . str_repeat("░", $empty);
    $output = sprintf(
      "\r%s: [%s] %d/%d (%d%%)",
      $prefix,
      $bar,
      $current,
      $total,
      (int)$percentage
    );
    
    return $this->write($output, true);
  }

  /**
   * Cria uma nova instância
   */
  public static function init(
  ): static {
    return new static;
  }
}
