<?php

namespace Shipyard\Controllers;

class ShipController extends ItemController {
    public function __construct() {
        parent::__construct();
        $this->modelType = 'Shipyard\Models\Ship';
        $this->modelName = 'Ship';
        $this->modelSlug = 'ship';
    }
}
