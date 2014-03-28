<?php

class Profile {

    protected $marks = array();


    public function __construct() {

    }


    public function setMark($name) {
        $this->marks[$name] = microtime(TRUE);
    }


    public function getMark($name) {
        if (!array_key_exists($name, $this->marks)) {
            return NULL;
        }
        return $this->marks[$name];
    }


    public function getMarks() {
        return $this->marks;
    }


    public function getInterval($mark1, $mark2) {
        if (!array_key_exists($mark1, $this->marks)) {
            return NULL;
        }
        if (!array_key_exists($mark2, $this->marks)) {
            return NULL;
        }
        return number_format(abs($this->marks[$mark2] - $this->marks[$mark1]), 4);
    }

}
