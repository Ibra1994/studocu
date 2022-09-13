<?php

namespace App\Console\Commands;

use App\Dto\FlashcardDto;
use App\Models\Flashcard;
use App\Models\User;
use App\Services\FlashcardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use PhpSchool\CliMenu\Action\GoBackAction;
use PhpSchool\CliMenu\Builder\CliMenuBuilder;
use PhpSchool\CliMenu\Builder\SplitItemBuilder;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuStyle;

class FlashcardCommand extends Command
{
    private $flashcardService;
    private MenuStyle $style;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashcard:interactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->style = new MenuStyle();
        $this->style->setBg('black');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle($continuePractice = false)
    {
        $menuBuilder = new CliMenuBuilder();

        $menuBuilder->setTitle('Flashcard App');

        $menuBuilder->addItem('Create a flashcard', function (CliMenu $menu) {
            $this->showCreateFlashcardMenu($menu);
        });

        $menuBuilder->addSubMenu('List all flashcards', function (CliMenuBuilder $builder) {
            $this->showListFlashcardMenu($builder);
        });

        $menuBuilder->addSubMenu('Practice', function (CliMenuBuilder $builder) {
            $this->showPracticeFlashcardMenu($builder);
        });

        $menuBuilder->addSubMenu('Stats', function (CliMenuBuilder $builder) {
            $this->showStatsFlashcardMenu($builder);
        });

        $menuBuilder->addItem('Reset', function (CliMenu $menu) {
            $this->resetProgress($menu);
        });

        $menu = $menuBuilder->build();

        if ($continuePractice) {
            //continue practice
            $menu->executeAsSelected($menu->getItems()[2]);
        } else {
            $menu->open();
        }
    }

    private function showCreateFlashcardMenu(CliMenu $menu)
    {
        $question = $menu->askText($this->style)
            ->setValidator(function ($input) {
                $validator = Validator::make(['question' => $input], Flashcard::getRules());
                return !$validator->fails();
            })
            ->setValidationFailedText('The question must be at least 1 characters and not be greater than 255 characters.')
            ->setPromptText('Please type question')
            ->ask();
        $answer = $menu->askText($this->style)
            ->setValidator(function ($input) {
                $validator = Validator::make(['answer' => $input], Flashcard::getRules());
                return !$validator->fails();
            })
            ->setValidationFailedText('The answer must be at least 1 characters and not be greater than 255 characters.')
            ->setPromptText('Please type correct answer for this question')
            ->ask();

        if (!empty($question->fetch()) && !empty($answer->fetch())) {
            $flashcardDto = FlashcardDto::fromParameters($question->fetch(), $answer->fetch());
            $this->getFlashcardService()->create($flashcardDto);

            $menu->close();
            $this->handle();
        }
    }

    private function showListFlashcardMenu(CliMenuBuilder $builder)
    {
        $builder->disableDefaultItems();

        $builder->setTitle('Flashcards list');

        $builder->addSplitItem(function (SplitItemBuilder $builder) {
            $builder->setGutter(2)
                ->addStaticItem('Question:')
                ->addStaticItem('Answer:');
        });

        $builder->addLineBreak('-');

        foreach ($this->getFlashcardService()->getFlashcards() as $flashCard) {
            $builder->addSplitItem(function (SplitItemBuilder $builder) use ($flashCard) {
                $builder->setGutter(2)
                    ->addStaticItem($flashCard->question)
                    ->addStaticItem($flashCard->answer);
            });
        }

        $builder->addItem('Go Back', new GoBackAction);
    }

    private function showPracticeFlashcardMenu(CliMenuBuilder $builder)
    {
        $builder->disableDefaultItems();

        $builder->setTitle('Practice');

        $builder->addSplitItem(function (SplitItemBuilder $builder) {
            $builder->setGutter(2)
                ->addStaticItem('Question:')
                ->addStaticItem('Status:');
        });

        $builder->addLineBreak('-');

        foreach ($this->getFlashcardService()->getPracticeProgress() as $flashCard) {
            $builder->addSplitItem(function (SplitItemBuilder $builder) use ($flashCard) {
                $builder->setGutter(2);

                $builder->addItem($flashCard->question, function (CliMenu $menu) use ($flashCard) {
                    $answer = $menu->askText($this->style)
                        ->setValidator(function ($input) {
                            $validator = Validator::make(['answer' => $input], [
                                'answer' => 'required|string|min:1|max:255'
                            ]);
                            return !$validator->fails();
                        })
                        ->setValidationFailedText('The answer must be at least 1 characters and not be greater than 255 characters.')
                        ->setPromptText($flashCard->question)
                        ->ask();

                    if (!empty($answer->fetch())) {
                        $this->getFlashcardService()->answer($flashCard, $answer->fetch());

                        $menu->close();
                        $this->handle(true);
                    }

                }, false, (bool)$flashCard->is_correct);

                $builder->addStaticItem(!isset($flashCard->is_correct) ? '[Not Answered]' : ($flashCard->is_correct ? '[Correct]' : '[Incorrect]'));
            });
        }

        $builder->addLineBreak('-');

        $builder->addSplitItem(function (SplitItemBuilder $builder) {
            $builder->setGutter(2)
                ->addStaticItem('Total correct answered questions:')
                ->addStaticItem($this->getFlashcardService()->getStats()->totalCorrectPercent.'%');
        });

        $builder->addItem('Go Back', new GoBackAction);
    }

    private function showStatsFlashcardMenu(CliMenuBuilder $builder)
    {
        $builder->disableDefaultItems();

        $builder->setTitle('Stats');

        $builder->addSplitItem(function (SplitItemBuilder $builder) {
            $builder->setGutter(2)
                ->addStaticItem('Total:')
                ->addStaticItem($this->getFlashcardService()->getStats()->total);
        });

        $builder->addSplitItem(function (SplitItemBuilder $builder) {
            $builder->setGutter(2)
                ->addStaticItem('Total answered questions:')
                ->addStaticItem($this->getFlashcardService()->getStats()->totalAnsweredPercent.'%');
        });

        $builder->addSplitItem(function (SplitItemBuilder $builder) {
            $builder->setGutter(2)
                ->addStaticItem('Total correct answered questions:')
                ->addStaticItem($this->getFlashcardService()->getStats()->totalCorrectPercent.'%');
        });

        $builder->addItem('Go Back', new GoBackAction);
    }

    private function resetProgress(CliMenu $menu)
    {
        $continue = $menu->cancellableConfirm('Are you sure you want to reset all progress?', $this->style)
            ->display();

        if ($continue) {
            $this->getFlashcardService()->resetProgress();

            $flash = $menu->flash("All practice progress erased!", $this->style);
            $flash->display();
        }

        $menu->close();
        $this->handle();
    }

    private function getFlashcardService(): FlashcardService
    {
        if (is_null($this->flashcardService)) {
            $user = User::firstOrCreate([
                'name' => 'user'
            ]);

            $this->flashcardService = new FlashcardService($user);
        }

        return $this->flashcardService;
    }
}
