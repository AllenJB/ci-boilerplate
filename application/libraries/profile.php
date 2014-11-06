<?php

class Profile
{

    protected $marks = array();


    public function __construct()
    {
    }


    public function setMark($name)
    {
        $this->marks[$name] = microtime(true);
    }


    public function getMark($name)
    {
        if (! array_key_exists($name, $this->marks)) {
            return null;
        }
        return $this->marks[$name];
    }


    public function getMarks()
    {
        return $this->marks;
    }


    public function getInterval($mark1, $mark2)
    {
        if (! array_key_exists($mark1, $this->marks)) {
            return null;
        }
        if (! array_key_exists($mark2, $this->marks)) {
            return null;
        }
        return number_format(abs($this->marks[$mark2] - $this->marks[$mark1]), 4);
    }
}
