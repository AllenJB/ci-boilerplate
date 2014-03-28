<?php
if (! (isset($url) && strlen($url)) ) {
    $url = '%%page%%';
}
?>
<div class="row">
    <div class="col-md-6">
        <div class="dataTables_info" style="position: relative; top: 10px;">
            Showing results <?= number_format($this->paginator->getFirstRow()) .' to '
                . number_format($this->paginator->getLastRow()) .' of '
                . number_format($this->paginator->getRows());
            ?>
        </div>
    </div>
    <div class="col-md-6" style="text-align: right;">
        <ul class="pagination" style="margin: 0px;">
            <?php
            $contextPages = 3;
            $curPage = $this->paginator->getCurPage();
            if ($curPage > $contextPages) {
                ?><li class="first"><a href="<?= str_replace('%%page%%', '1', $url); ?>">|&lt;&lt;</a></li><?php
            }
            if ($curPage > 1) {
                ?><li class="prev"><a href="<?= str_replace('%%page%%', ($curPage - 1), $url); ?>">&lt;</a></li><?php
            }

            for ($i = $curPage - $contextPages; $i < $curPage + $contextPages; $i++) {
                if ($i < 1) {
                    continue;
                }
                if ($i > $this->paginator->getPageCount()) {
                    break;
                }

                if ($i == $curPage) {
                    ?><li class="active"><a href="<?= str_replace('%%page%%', $i, $url); ?>"><?= $i; ?></a></li><?php
                } else {
                    ?><li><a href="<?= str_replace('%%page%%', $i, $url); ?>"><?= $i; ?></a></li><?php
                }
            }

            if ($curPage < $this->paginator->getPageCount()) {
                ?><li class="next"><a href="<?= str_replace('%%page%%', ($curPage + 1), $url); ?>">&gt;</a></li><?php
            }
            if ($curPage < ($this->paginator->getPageCount() - $contextPages)) {
                ?><li class="last"><a href="<?= str_replace('%%page%%', $this->paginator->getPageCount(), $url); ?>">&gt;&gt;</a></li><?php
            }
            ?>
        </ul>
    </div>
</div>
