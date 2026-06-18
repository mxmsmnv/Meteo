<?php namespace ProcessWire;

abstract class MeteoProvider {

    use MeteoWmoDictionary;

    protected Meteo $module;

    public function __construct(Meteo $module) {
        $this->module = $module;
    }

    abstract public function fetch(float $lat, float $lon, array $opts): array|false;
}
