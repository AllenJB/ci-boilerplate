<?php

class Paginator {

    protected $total = 0;

    protected $perPage = 20;

    protected $curPage = 1;

    public function __construct() {

    }

    public function setItemsPerPage($perPage) {
        $this->perPage = $perPage;
        return $this;
    }

    public function setTotalRows($total) {
        $this->total = $total;
        return $this;
    }

    public function setCurPage($page) {
        $this->curPage = $page;
        return $this;
    }

    public function getOffset() {
        return ($this->curPage - 1) * $this->perPage;
    }

    public function getPerPage() {
        return $this->perPage;
    }

    public function getPageCount() {
        return ceil($this->total / $this->perPage);
    }

    public function getCurPage() {
        return $this->curPage;
    }

    public function getFirstRow() {
        return $this->getOffset() + 1;
    }

    public function getLastRow() {
        $maxThisPage = ($this->getFirstRow() + $this->getPerPage() - 1);
        return ($maxThisPage < $this->total ? $maxThisPage : $this->total);
    }

    public function getRows() {
        return $this->total;
    }

}
