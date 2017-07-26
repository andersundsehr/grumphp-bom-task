<?php

namespace PLUS\GrumPHPBomTask;

use GrumPHP\Runner\TaskResult;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BomFixerTask
 *
 * @author Matthias Vogel <matthias.vogel@pluswerk.ag>
 * @package PLUS\GrumphpBomTask
 */
class BomFixerTask extends AbstractExternalTask
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'plus_bom_fixer';
    }

    /**
     * @return \Symfony\Component\OptionsResolver\OptionsResolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function getConfigurableOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'triggered_by' => ['php', 'css', 'scss', 'less', 'json', 'sql', 'yml', 'txt'],
            ]
        );

        $resolver->addAllowedTypes('triggered_by', ['array']);

        return $resolver;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function canRunInContext(ContextInterface $context)
    {
        return ($context instanceof GitPreCommitContext || $context instanceof RunContext);
    }

    /**
     * @param ContextInterface $context
     *
     * @return \GrumPHP\Runner\TaskResult
     * @throws \GrumPHP\Exception\RuntimeException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function run(ContextInterface $context)
    {
        $config = $this->getConfiguration();
        $files = $context->getFiles()->extensions($config['triggered_by']);
        if (0 === count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        if (is_file('./vendor/bin/fixbom')) {
            $fixCommand = './vendor/bin/fixbom';
        } else if (is_file('./bin/fixbom')) {
            $fixCommand = './bin/fixbom';
        } else {
            $fixCommand = 'fixbom';
        }
        $shouldGetFixedLog = [];
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $execFile = $file->getPathname();
            if ($this->isFileWithBOM($execFile)) {
                $shouldGetFixedLog[] = $execFile . " has BOM and should be fixed";
                $fixCommand .= " '" . $execFile . "'";
            }
        }

        if (count($shouldGetFixedLog) > 0) {
            return TaskResult::createFailed(
                $this,
                $context,
                implode(PHP_EOL, $shouldGetFixedLog) . PHP_EOL
                . "you can use this to fix them:" . PHP_EOL .
                $fixCommand
            );
        }
        return TaskResult::createPassed($this, $context);
    }


    /**
     * @param string $filename
     * @param string $search
     *
     * @return bool
     */
    protected function fileInfoSearch($filename, $search)
    {
        $output = [];
        exec('file ' . '"' . $filename . '"', $output, $returnVar);
        if ($returnVar === 0 && !empty($output[0]) && strpos($output[0], $search) !== false) {
            return true;
        }
        return false;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public function isFileWithBOM($filename)
    {
        return $this->fileInfoSearch($filename, 'BOM');
    }
}
