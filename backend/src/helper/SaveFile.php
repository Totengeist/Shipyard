<?php

namespace IVParser;

use IVParser\IVFile;
use IVParser\ShipFile;

class SaveFile extends IVFile {
    public function __construct($structure = null, $level = 0, $subfiles = ['/Layer' => ShipFile::class]) {
        parent::__construct($structure, $level, $subfiles);
    }
    
    public function get_fleet() {
        $fleet = [];
        foreach( $this->get_section("Layer") as $ship) {
            if( $ship->info["Type"] == "FriendlyShip" ) {
                $fleet[] = $ship;
            }
        }
        return $fleet;
    }
    
    public function print_info() {
        $fleet = $this->get_fleet();
        $fleet_cnt = count($fleet);
        $ships = [];
        foreach( $fleet as $ship) {
            $ships[] = $ship->info["Name"];
        }
        
        $template = 'Your save file has %d active missions, %d ships in your fleet, and %d ships in this system. You fleet contains: %s.';
        echo sprintf($template, 
            count($this->get_section("Missions/Missions")->sections),
            $fleet_cnt,
            count($this->get_section("Layer"))-$fleet_cnt,
            implode(", ", $ships)
        );
    }
}
