<?php $this->out('header.inc'); ?>

<?php

if ($this->events) {
    $lastDate = $lastExit = null;
    function tableEnd ($lastDate, $table, $fareTotal, $topupTotal, $daysBalance, $lowDaysBalance) { ?>
        <p class="totals">
            Fares: <strong>£<?php out(sprintf('%.2f', $fareTotal/100)); ?></strong>
            <span class="bull">•</span>
            Topups: <strong>£<?php out(sprintf('%.2f', $topupTotal/100)); ?></strong>
        </p>
        <?php
        if ($lastDate->format('j') > 1) {
            $firstBalDay = $lastDate->format('j');
            $firstBal = $daysBalance[$firstBalDay];
            foreach (range($firstBalDay - 1, 1) as $d) {
                $daysBalance[$d] = false;
                $lowDaysBalance[$d] = null;
            }
        }
        $reversed = array_reverse($daysBalance, true);
        $lastBal = $lastBalDay = null; ?>
        <table class="graph">
            <tr class="bars">
                <?php foreach ($reversed as $day => $bal):
                    $lowBal = $lowDaysBalance[$day];
                    $outBal = $bal;
                    $noTravel = false;
                    if ($outBal === false):
                        $noTravel = true;
                        $outBal = $lastBal ? $lastBal : $firstBal;
                    endif; ?>
                    <td><<?php if ($bal !== false): ?>a href="#<?php out($lastDate->format('Y-m-') . $day); ?>"<?php else: ?>span<?php endif; ?> class="bar<?php if ($noTravel): ?> notravel<?php endif; ?>" style="height: <?php out($outBal/10); ?>px;" title="£<?php out(sprintf('%.2f', $outBal/100)); ?><?php if ($lowBal && $lowBal < $bal): ?> (low £<?php out(sprintf('%.2f', $lowBal/100)); ?>)<?php endif; ?>"><?php if ($lowBal && $lowBal < $bal): ?><span class="low" style="height: <?php out($lowBal/10); ?>px;"></span><?php endif; ?><span class="limit"></span></<?php if ($bal !== false): ?>a<?php else: ?>span<?php endif; ?>></td>
                <?php $lastBal = $bal ? $bal : $lastBal;
                $lastBalDay = $bal ? $day : $lastBalDay;
                endforeach; ?>
            </tr>
            <tr class="days">
                <?php foreach ($reversed as $day => $bal): ?>
                    <td><?php out($day); ?></td>
                <?php endforeach; ?>
            </tr>
        </table>
        <?php echo $table; ?>
            </tbody>
        </table>
    <?php }
    foreach ($this->events as $event):
        if (!$lastExit && (!$lastDate || $lastDate->format('M Y') != $event->creation_date->format('M Y'))):
            if ($lastDate) {
                tableEnd($lastDate, ob_get_clean(), $fareTotal, $topupTotal, $daysBalance, $lowDaysBalance);
            }
            $lastDate = $lastExit = null;
            $fareTotal = $topupTotal = 0;
            $daysBalance = array();
            $lowDaysBalance = array();
            ?>
            <h3 class="month"><?php out($event->creation_date->format('M Y')); ?></h3>
            <?php ob_start(); ?>
            <table class="history">
                <thead>
                    <tr>
                        <th class="date">Date</th>
                        <th class="action">Event</th>
                        <th class="location">Location</th>
                        <th class="fare">Fare</th>
                        <th class="balance">Balance</th>
                    </tr>
                </thead>
                <tbody>
        <?php endif;
        if (!$lastDate) {
            // Fill out rest of days of month
            foreach (range($event->creation_date->format('t'), $event->creation_date->format('j')) as $d) {
                $daysBalance[$d] = null;
                $lowDaysBalance[$d] = null;
            }
        } else if ($lastDate->format('j') != $event->creation_date->format('j')) {
            // Fill in dates in between
            foreach (range($lastDate->format('j') - 1, $event->creation_date->format('j')) as $d) {
                $daysBalance[$d] = false;
                $lowDaysBalance[$d] = null;
            }
        }
        if ($event->isExit()) {
            $lastExit = $event;
        } else {
            if ($lastExit) {
                $fare = $event->fare + $lastExit->fare;
                $db = $lastExit->balance;
            } else {
                $fare = $event->fare;
                $db = $event->balance;
            }
            $day = $event->creation_date->format('j');
            if (!$daysBalance[$day]) {
                $daysBalance[$day] = $db;
            }
            if (!$lowDaysBalance[$day] || $lowDaysBalance[$day] > $db) {
                $lowDaysBalance[$day] = $db;
            }
            
            $fareSign = '';
            if ($fare < 0) {
                $fare *= -1;
                $fareTotal += $fare;
                $class = 'debit';
                $fareSign = '-';
                if ($fare > FARE_WARNING) {
                    $class .= ' warning';
                }
            } else if ($fare > 0) {
                $class = 'credit';
                $topupTotal += $fare;
            }
            
            if ($lastExit) {
                $balance = $lastExit->balance;
            } else {
                $balance = $event->balance;
            }
            $balanceSign = '';
            if ($balance < 0) {
                $balance *= -1;
                $balanceSign = '-';
            }
            $newDate = false;
            if (!$lastDate || $lastDate->format('Y-m-j') != $event->creation_date->format('Y-m-j')) {
                $newDate = true;
                $class .= ' newDate';
            }
            if ($lastExit):
            ?>
                <tr class="<?php out($class); ?>"<?php if ($newDate): ?>
                    id="<?php out($lastExit->creation_date->format('Y-m-j')); ?>"
                <?php endif; ?>>
                    <td class="date">
                        <?php out($event->creation_date->format('D d')); ?>
                        <?php out($event->creation_date->format('H:i')); ?>
                        ⟶
                        <?php out($lastExit->creation_date->format('H:i')); ?>
                    </td>
                    <td class="action">Journey</td>
                    <td class="location">
                        <?php if (preg_match("/.* \[london overground\]$/i", $event->location) || preg_match("/.* \[london overground\]$/i", $lastExit->location)): ?>
                            <img src="/overground.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* (?:\[london underground\])|(?:line[s]?\))|(?:gate[s]? only\))$/i", $event->location) || preg_match("/.* (?:\[london underground\])|(?:line[s]?\))|(?:gate[s]? only\))$/i", $lastExit->location)): ?>
                            <img src="/tube.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* DLR$/i", $event->location) || preg_match("/.* DLR$/i", $lastExit->location)): ?>
                            <img src="/dlr.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* \[national rail\]$/i", $event->location) || preg_match("/.* \[national rail\]$/i", $lastExit->location)): ?>
                            <img src="/Rail.gif">
                        <?php endif; ?>
                        <?php out($event->location); ?>
                        ⟼
                        <?php out($lastExit->location); ?>
                    </td>
                    <td class="fare"><?php if ($fare): ?><?php out($fareSign); ?>£<?php out(sprintf('%.2f', $fare/100)); ?><?php endif; ?></td>
                    <td class="balance"><?php if ($balance): ?><?php out($balanceSign); ?>£<?php out(sprintf('%.2f', $balance/100)); ?><?php endif; ?></td>
                </tr>
            <?php $lastExit = null;
            else: ?>
                <tr class="<?php out($class); ?>"<?php if (!$lastDate || $lastDate->format('Y-m-j') != $event->creation_date->format('Y-m-j')): ?>
                    id="<?php out($event->creation_date->format('Y-m-j')); ?>"
                <?php endif; ?>>
                    <td class="date"><?php out($event->creation_date->format('D d H:i')); ?></td>
                    <td class="action"><?php out($event->action); ?></td>
                    <td class="location">
                        <?php if (preg_match("/^bus .*$/i", $event->location)): ?>
                            <img src="/Bus.gif">
                        <?php elseif (preg_match("/.* tramstop$/i", $event->location)): ?>
                            <img src="/tram.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* \[london overground\]$/i", $event->location)): ?>
                            <img src="/overground.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* (?:\[london underground\])|(?:line[s]?\))|(?:gate[s]? only\))$/i", $event->location)): ?>
                            <img src="/tube.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* DLR$/i", $event->location)): ?>
                            <img src="/dlr.gif" width="26" height="21">
                        <?php elseif (preg_match("/.* \[national rail\]$/i", $event->location)): ?>
                            <img src="/Rail.gif">
                        <?php endif; ?>
                        <?php out($event->location); ?>
                    </td>
                    <td class="fare"><?php if ($fare): ?><?php out($fareSign); ?>£<?php out(sprintf('%.2f', $fare/100)); ?><?php endif; ?></td>
                    <td class="balance"><?php if ($balance): ?><?php out($balanceSign); ?>£<?php out(sprintf('%.2f', $balance/100)); ?><?php endif; ?></td>
                </tr>
            <?php endif;
            $lastDate = $event->creation_date;
        }
    endforeach;
    tableEnd($lastDate, ob_get_clean(), $fareTotal, $topupTotal, $daysBalance, $lowDaysBalance); ?>
<?php } else { ?>
    <p>No journey data…</p>
<?php } ?>

<?php $this->out('footer.inc'); ?>
