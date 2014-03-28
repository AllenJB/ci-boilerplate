<?php
if ((!isset($flashMessages)) && isset($FlashMessages)) {
    $flashMessages = $FlashMessages;
}

if (isset($flashMessages) && is_array($flashMessages) && count($flashMessages)) {
    ?>
    <ul class="flash-messages">
        <?php
        // We could loop over $flashMessages directly,
        // but this method should ensure message types always appear in the same order
        $types = array('error', 'warning', 'success', 'info');
        foreach ($types as $type) {
            if (!array_key_exists($type, $flashMessages)) {
                continue;
            }
            $messages = $flashMessages[$type];
            if (! (is_array($messages) && count($messages)) ) {
                continue;
            }
            $classList = array ('flash-message', $type, 'alert', 'alert-'. $type);
            if ($type == 'error') {
                $classList[] = 'alert-danger';
            }

            foreach ($messages as $message) {
                if (strlen($message) < 1) {
                    continue;
                }

                ?>
                <li class="<?= join(' ', $classList); ?>">
                    <span class="icon"></span>
                    <?= nl2br(html_escape($message)); ?>
                </li>
                <?php
            }
        }

        ?>
    </ul>
    <?php
}
