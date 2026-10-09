<?php

declare(strict_types=1);
namespace LocalPHP\Database;
final class ForeignKeyDefinition
{
    public ?string $referenceTable=null; public ?string $referenceColumn=null; public string $onDelete='RESTRICT'; public string $onUpdate='RESTRICT';
    public function __construct(public string $column) {}
    public function references(string $column): self { $this->referenceColumn=$column; return $this; }
    public function on(string $table): self { $this->referenceTable=$table; return $this; }
    public function onDelete(string $action): self { $this->onDelete=$this->action($action); return $this; }
    public function onUpdate(string $action): self { $this->onUpdate=$this->action($action); return $this; }
    private function action(string $action): string { $action=strtoupper($action); if (!in_array($action,['CASCADE','RESTRICT','SET NULL','NO ACTION'],true)) throw new \InvalidArgumentException('Invalid foreign-key action.'); return $action; }
}
