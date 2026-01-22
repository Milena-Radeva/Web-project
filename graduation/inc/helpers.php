<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function stage_label(int $s): string {
  return match($s){
    0 => 'Регистриран',
    1 => 'Потвърден',
    2 => 'На церемония',
    3 => 'Завършен',
    default => 'Неизвестно'
  };
}
function stage_class(int $s): string {
  return match($s){
    0 => 'stage-0',
    1 => 'stage-1',
    2 => 'stage-2',
    3 => 'stage-3',
    default => 'stage-x'
  };
}
