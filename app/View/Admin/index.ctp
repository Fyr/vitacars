<?
    $this->Html->css('/Table/css/grid', array('inline' => false));

    $aTitles = array('Brand' => 'Брэндов', 'Category' => 'Категорий', 'Subcategory' => 'Подкатегорий', 'PureProduct' => 'Продуктов');
    $aStatusMsg = array(
        Task::CREATED => 'Запуск задачи...',
        Task::RUN => 'Выполняется...',
        Task::DONE => 'Выполнена',
        Task::ERROR => '<b class="err-msg">Ошибка!</b>',
        Task::ABORT => '<b>Попытка прервать задачу...</b>',
        Task::ABORTED => '<b>Прервана!</b>',
        Task::TERMINATED => 'Снята',
        'Hangs' => '<b class="err-msg">Задача не отвечает!</b>',
        //    'noCache' => '<b class="err-msg">Отсутствует кэш!</b>'
    );

    $aTaskCols = array('Создана', 'Имя задачи', 'Прогресс', 'Статус');
?>
<style>
    td.col {
        text-align: left;
        vertical-align: top;
        width: 50%;
        padding-right: 20px;
    }
</style>
<?=$this->element('admin_title', array('title' => 'Сводка по системе'))?>
<div class="span8 offset2">
<?
    echo $this->element('admin_content');
?>
    <table width="100%">
    <tr>
        <td class="col">
            <!-- Left column -->
            <b>Фоновые задачи</b><br/>
            За сегодня: <b><?=($todayTasks) ? $todayTasks : '-'?></b> задач(и)<br/>
            <table class="grid">
            <!--thead>
            <tr class="first table-gradient">
<?
    foreach($aTaskCols as $title) {
?>
                <th><a class="grid-unsortable" href="javascript:;"><?=$title?></a></th>
<?
    }
?>

            </tr>
            </thead-->
            <tbody>
<?
            foreach($aTasks as $row) {
                $task_id = $row['Task']['id'];
                if ($aCached[$task_id]) {
                    // берем прогресс из кэша т.к. БД могла еще не обновится
                    $row['Task']['progress'] = $this->element('AdminTasks/progress_str', $aCached[$task_id]);
                } else {
                    $row['Task']['progress'] = $this->element('AdminTasks/progress_str', $row['Task']);
                }

                $status = $row['Task']['status'];
                if (isset($aHangs[$task_id])) {
                    $row['Task']['status'] = $aStatusMsg['Hangs'];
                } else {
                    $row['Task']['status'] = (isset($aStatusMsg[$status])) ? $aStatusMsg[$status] : '???';
                }
                /*
                if ($status == Task::ERROR) {
                    $row['Task']['status'].= ($row['Task']['xdata']) ? ' '.unserialize($row['Task']['xdata']) : 'Неизвестная ошибка!';
                } elseif ($status == Task::ABORTED && $row['Task']['xdata']) {
                    $row['Task']['status'] = unserialize($row['Task']['xdata']);
                }
*/

                if (!isset($aHangs[$task_id]) && in_array($status, array(Task::CREATED, Task::RUN))) {
                    // если задача нормально выполняется - делаем ссылку на ее просмотр
                    $row['Task']['task_name'] = $this->Html->link($aMainTaskOptions[$row['Task']['task_name']],
                        array('controller' => 'AdminTasks', 'action' => 'task', $row['Task']['task_name']),
                        array('target' => '_blank', 'title' => 'Просмотр задачи')
                    );
                } else {
                    $row['Task']['task_name'] = $aMainTaskOptions[$row['Task']['task_name']];
                }
?>
            <tr class="grid-row">
                <td><?=$row['Task']['created']?></td>
                <td><?=$row['Task']['task_name']?></td>
                <td><?=$row['Task']['progress']?></td>
                <td><?=$row['Task']['status']?></td>
            </tr>
<?
            }
?>
            </tbody>
            </table>
            <a class="btn btn-mini btn-primary pull-right" href="<?=$this->Html->url(array('controller' => 'AdminTasks', 'action' => 'index'))?>">
                подробнее
                <i class="icon-white icon-chevron-right"></i>
            </a>
            <!-- /Left column -->
        </td>
        <td class="col">
            <!-- Right column -->
            <div>
            <b>Статистика по системе</b><br/>
            <table align="left" class="grid" width="100%">
            <tbody>
<?
    foreach($aCount as $model => $val) {
?>
            <tr class="grid-row">
                <td><?=$aTitles[$model]?></td>
                <td align="right"><?=$val?></td>
            </tr>
<?
    }
?>
            </tbody>
            </table>
            </div>
            <div>
            <b>Пользователи онлайн</b><br/>
            <table align="left" class="grid" width="100%">
            <tbody>
<?
    foreach($aUsersOnline as $row) {
        $status = (strtotime($row['User']['last_action']) > (time() - MINUTE * 5)) ? 'success' : 'warning';
?>
            <tr class="grid-row">
                <td>
                    <span class="label label-<?=$status?>">&nbsp;&nbsp;&nbsp;</span>
                </td>
                <td width="80%"><?=$row['User']['username']?></td>
                <td nowrap="nowrap"><?=$row['User']['last_action']?></td>
            </tr>
<?
    }
?>
            </tbody>
            </table>
            </div>
            <!-- /Right column -->
        </td>
    </tr>
    </table>
<?
    echo $this->element('admin_content_end');
?>
</div>
