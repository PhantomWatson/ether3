<div class="row">
    <div class="col-sm-offset-2 col-sm-8">
        <?= $this->Form->create(
            $thought,
            [
                'url' => [
                    'controller' => 'Thoughts',
                    'action' => $this->request->action == 'edit' ? 'edit' : 'add'
                ],
                'id' => 'ThoughtAddForm'
            ]
        ) ?>

        <?php if (isset($suggestedThoughtwords)): ?>
            <div id="suggested-words">
                Not sure what to write about?
                Here are some suggestions:
                <ul>
                    <?php foreach ($suggestedThoughtwords as $word): ?>
                        <li>
                            <button><?= $word ?></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php $this->append('buffered_js'); ?>
                suggestedWords.init();
            <?php $this->end(); ?>
        <?php endif; ?>

        <?= $this->Form->input(
            'word',
            [
                'class' => 'form-control',
                'label' => [
                    'class' => 'control-label',
                    'text' => 'Thoughtword'
                ],
                'placeholder' => 'Enter a word to associated your thought with'
            ]
        ) ?>

        <?= $this->Form->input(
            'thought',
            [
                'class' => 'form-control',
                'label' => [
                    'class' => 'control-label',
                    'text' => 'Thought'
                ],
                'type' => 'textarea',
                'placeholder' => 'What\'s on your mind?'
            ]
        ) ?>

        <div class="options row">
            <div class="form-group col-md-5">
                <?= $this->Form->input(
                    'comments_enabled',
                    [
                        'label' => 'Allow comments',
                        'type' => 'checkbox'
                    ]
                ) ?>
            </div>

            <div class="form-group col-md-5">
                <?= $this->Form->input(
                    'anonymous',
                    [
                        'label' => 'Post anonymously',
                        'type' => 'checkbox'
                    ]
                ) ?>
            </div>

            <div class="col-md-2">
                <?= $this->Form->submit(
                    'Think',
                    ['class' => 'btn btn-default btn-block']
                ) ?>
                <?= $this->Form->end(); ?>
            </div>
        </div>

        <p>
            Styles like *<em>italics</em>* and **<strong>bold</strong>** can be applied with Markdown. For a full list of supported styles, consult the
            <?= $this->Html->link('Markdown styling guide',
                [
                    'controller' => 'Pages',
                    'action' => 'markdown'
                ],
                ['target' => '_blank']
            ) ?>
        </p>
    </div>
</div>