<?php

namespace ProcessWire;

trait AdminStyle
{
  public function loadStyle($style)
  {
    // do everything below only on admin pages
    if ($this->wire->page->template != 'admin') return;

    $config = $this->wire()->config;
    $min = !$config->debug;

    $compiled = $config->paths->assets . "admin";
    if ($min) $compiled .= ".min.css";
    else $compiled .= ".css";

    $config->AdminThemeUikit = [
      'style' => $style,
      'compress' => $min,
      'customCssFile' => $compiled,
      'recompile' => @(filemtime($style) > filemtime($compiled)),
      'vars' => $this->getStyleVars(),
    ];
  }

  /**
   * You can implement that method in your style module to define
   * custom variables from PHP
   */
  public function getStyleVars(): array
  {
    return [];
  }

  /**
   * Unlink admin.css to force recompile
   */
  public function resetAdminStyle()
  {
    $files = $this->wire->files;
    $file = $this->wire->config->paths->assets . "admin";
    if (is_file($file . ".css")) $files->unlink($file . ".css");
    if (is_file($file . ".min.css")) $files->unlink($file . ".min.css");
  }

  public function ___install()
  {
    $this->resetAdminStyle();
  }

  public function ___uninstall()
  {
    $this->resetAdminStyle();
  }
}
