<?php
namespace AUS\GrumphpBomTask;

use GrumPHP\Runner\TaskResult;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BomFixerTask
 *
 * @author Matthias Vogel <m.vogel@andersundsehr.com>
 * @package AUS\GrumphpBomTask
 */
class BomFixerTask extends AbstractExternalTask
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'aus_bom_fixer';
    }

    /**
     * @return OptionsResolver
     */
    public function getConfigurableOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array(
            'auto_fix' => false,
            'triggered_by' => ['php', 'css', 'scss', 'less', 'json', 'sql', 'yml', 'txt'],
        ));

        $resolver->addAllowedTypes('auto_fix', array('bool'));
        $resolver->addAllowedTypes('triggered_by', array('array'));

        return $resolver;
    }

    /**
     * @param ContextInterface $context
     * @return bool
     */
    public function canRunInContext(ContextInterface $context)
    {
        return ($context instanceof GitPreCommitContext || $context instanceof RunContext);
    }

    /**
     * @param ContextInterface $context
     * @return TaskResult
     */
    public function run(ContextInterface $context)
    {
        $currentWorkDirectory = getcwd();
        $config = $this->getConfiguration();
        $files = $context->getFiles()->extensions($config['triggered_by']);
        if (0 === count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $errorLog = [];
        $fixedLog = [];
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            chdir(dirname($file->getRealPath()));
            $execFile = $file->getPathname();
            $debugLog[] = $execFile;
            if ($this->isFileWithBOM($execFile)) {
                if ($config['auto_fix']) {
                    $fileContent = file_get_contents($execFile);
                    $bom = pack('H*', 'EFBBBF');
                    $fileContent = preg_replace("/^$bom/", '', $fileContent);
                    unlink($execFile);
                    file_put_contents($execFile, $fileContent);
                    $fixedLog[] = $execFile . " had BOM and is fixed now";
                } else {
                    $errorLog[] = $execFile . " has BOM and should be fixed";
                }
            }
        }
        chdir($currentWorkDirectory);

        if (count($errorLog) > 0) {
            return TaskResult::createFailed($this, $context, implode(PHP_EOL, $errorLog));
        }
        if (count($fixedLog) > 0) {
            return TaskResult::createNonBlockingFailed($this, $context, implode(PHP_EOL, $fixedLog));
        }
        return TaskResult::createPassed($this, $context);
    }


    /**
     * @param string $filename
     * @param string $search
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
     * @return bool
     */
    public function isFileWithBOM($filename)
    {
        return $this->fileInfoSearch($filename, 'BOM');
    }
}
