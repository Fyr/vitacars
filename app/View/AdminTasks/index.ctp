<?
    $objectType = 'Task';
    $title = __('Bkg.tasks');// $this->ObjectType->getTitle('index', $objectType);
    $aStatusMsg = array(
        Task::CREATED => 'Запуск задачи...',
        Task::RUN => 'Выполняется...',
        Task::DONE => 'Выполнена',
        Task::ERROR => '<b class="err-msg">Ошибка!</b>',
        Task::ABORT => 'Попытка прервать задачу...',
        Task::ABORTED => 'Прервана по неизвестной причине!',
        Task::TERMINATED => 'Снята',
        'Hangs' => '<b class="err-msg">Задача не отвечает!</b>',
        'noCache' => '<b class="err-msg">Отсутствует кэш!</b>'
    );

    $actions = $this->PHTableGrid->getDefaultActions($objectType);
    unset($actions['table']['add']);
    unset($actions['row']['edit']);

    $columns = $this->PHTableGrid->getDefaultColumns($objectType);
    $columns['Task.task_name']['label'] = 'Задача';
    $columns['Task.status']['label'] = 'Статус';
    $columns['Task.user_id']['label'] = __('Username');
    $columns['Task.user_id']['format'] = 'string';
    $columns['Task.exec_time']['label'] = 'Время выполнения';
    $columns['Task.exec_time']['format'] = 'string';
    $columns['Task.progress']['label'] = 'Прогресс';
    $columns['Task.progress']['format'] = 'string';
    unset($columns['Task.total']);
    unset($columns['Task.xdata']);
    $columns['Task.active']['format'] = 'string';
    $columns['Task.active']['align'] = 'center';
    $columns['Task.cached'] = array(
        'key' => 'Task.cached',
        'label' => 'Кэш',
        'format' => 'string',
        'align' => 'center'
    );
    $columns['Task.terminate'] = array(
        'key' => 'Task.terminate',
        'label' => 'Снять',
        'format' => 'string',
        'align' => 'center'
    );
    $aRows = array('legend-red' => array(), 'legend-red' => array(), 'legend-green' => array());
    foreach($data as &$row) {
        $task_id = $row['Task']['id'];
        $row['Task']['user_id'] = $aUsers[$row['Task']['user_id']];
        $row['Task']['exec_time'] = $this->PHTime->niceShortTime($row['Task']['exec_time']);
        $row['Task']['active'] = ($row['Task']['active']) ? '<i class="icon-color icon-check"></i>' : '';
        $row['Task']['cached'] = ($aCached[$task_id]) ? '<i class="icon-color icon-check"></i>' : '';
        $row['Task']['progress'] = $this->element('AdminTasks/progress_str', $row['Task']);

        $status = $row['Task']['status'];
        $row['Task']['status'] = (isset($aStatusMsg[$status])) ? $aStatusMsg[$status] : '???';
        if ($status == Task::ERROR) {
            $row['Task']['status'].= ($row['Task']['xdata']) ? ' '.unserialize($row['Task']['xdata']) : 'Неизвестная ошибка!';
        } elseif ($status == Task::ABORTED && $row['Task']['xdata']) {
            $row['Task']['status'] = unserialize($row['Task']['xdata']);
        }

        $task_name = $row['Task']['task_name'];

        if (!isset($aHangs[$task_id]) && in_array($status, array(Task::CREATED, Task::RUN))) {
            $row['Task']['task_name'] = $this->Html->link($aTaskOptions[$row['Task']['task_name']],
                array('action' => 'task', $row['Task']['task_name']),
                array('target' => '_blank', 'title' => 'Просмотр задачи')
            );
        } else {
            $row['Task']['task_name'] = $aTaskOptions[$row['Task']['task_name']];
        }
        /** Раскраска задач:
         * зеленый - задача нормально выполняется
         * желтый - ошибки и отработанные прерывания юзером
         * красный - не отвечает
         */
        $row['Task']['terminate'] = '';
        if (isset($aHangs[$task_id])) {
            $aRows['legend-red'][] = $task_id;
            $row['Task']['status'].= ($aHangs[$task_id]) ? $aStatusMsg['Hangs'] : $aStatusMsg['noCache'];
            $row['Task']['terminate'] = $this->Html->link(
                '<i class="icon-color icon-power-down"></i>',
                array('action' => 'terminate', $task_id),
                array('escape' => false, 'title' => 'Снять задачу'),
                'Вы уверены, что хотите снять задачу?'
            );
        } elseif (in_array($status, array(Task::ABORTED, Task::ERROR))) {
            $aRows['legend-yellow'][] = $task_id;
        } elseif (in_array($status, array(Task::RUN, Task::CREATED))) {
            $aRows['legend-green'][] = $task_id;
        }

        if (isset($aChildTasks[$task_id])) {
            $items = 'подзадач(и)';
            $detail_nums = array();
            $aSubtaskFields = array(
                'status' => array(),
                'progress' => array(),
                'exec_time' => array(),
                'active' => array(),
                'cached' => array()
            );
            $aStatus = array();
            $aProgress = array();
            $aExecTime = array();
            $i = 0;
            foreach($aChildTasks[$task_id] as $task) {
                $detail_nums[] = $this->Html->tag('span', ++$i.'. '.$aTaskOptions[$task['task_name']], array('class' => 'subTask'));

                $status = $task['status'];
                $task['status'] = (isset($aStatusMsg[$status])) ? $aStatusMsg[$status] : '???';
                if ($status == Task::ERROR) {
                    $task['status'].= ($task['xdata']) ? ' '.unserialize($task['xdata']) : 'Неизвестная ошибка!';
                } elseif ($status == Task::ABORTED && $task['xdata']) {
                    $task['status'] = unserialize($row['Task']['xdata']);
                }

                $aSubtaskFields['status'][] = $this->Html->tag('span', $task['status'], array('class' => ''));
                $aSubtaskFields['progress'][] = $this->Html->tag('span', $this->element('AdminTasks/progress_str', $task), array('class' => ''));
                $aSubtaskFields['exec_time'][] = $this->Html->tag('span', $this->PHTime->niceShortTime($task['exec_time']), array('class' => ''));
                $aSubtaskFields['active'][] = $this->Html->tag('span', ($task['active']) ? '<i class="icon-color icon-check"></i>' : '', array('class' => ''));
                $aSubtaskFields['cached'][] = $this->Html->tag('span', ($aCached[$task_id]) ? '<i class="icon-color icon-check"></i>' : '', array('class' => ''));
            }

            foreach($aSubtaskFields as $field => $rows) {
                $div = $this->Html->tag('div', implode('<br/>', $rows), array('style' => 'display: none;'));
                $row['Task'][$field].= $this->Html->tag('span', $div, array('class' => 'detail-nums'));
            }
            $row['Task']['task_name'].= $this->element('AdminProduct/detail_nums', compact('detail_nums', 'items'));
        }

    }
    echo $this->element('admin_title', compact('title'));
    echo $this->PHTableGrid->render($objectType, compact('actions', 'columns', 'data'));
?>
<style>
    .grid > tbody > tr.grid-row > td.align-top { vertical-align: top; }
    .subTask { margin-left: 10px;}
</style>
<script type="text/javascript">
$(document).ready(function(){
    $('.detail-nums .expand-num').click(function(){
        $(this).hide();
        $(this).parent().find('.collapse-num').show();

        var $tr = $(this).closest('tr');
        $('.detail-nums div', $tr).each(function(){
            $(this).parent().parent().addClass('align-top');
        });

        $('.detail-nums div', $tr).slideDown('fast', function(){
            // $(this).parent().find('.collapse-num').show();
        });
    });
    $('.detail-nums .collapse-num').click(function(){
        var $tr = $(this).closest('tr');
        $('.detail-nums div', $tr).slideUp('fast', function(){
            $(this).parent().find('.collapse-num').hide();
            $(this).parent().find('.expand-num').show();

            $('.detail-nums div', $tr).each(function(){
                $(this).parent().parent().removeClass('align-top');
            });
        });
    });

    aRows = <?=json_encode($aRows)?>;
    for(var legend in aRows) {
        if (aRows[legend].length) {
            for(var i = 0; i < aRows[legend].length; i++) {
                $('#row_' + aRows[legend][i]).addClass(legend);
            }
        }
    }
});
</script>
