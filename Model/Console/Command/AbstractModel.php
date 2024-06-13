<?php
/**
 * O2TI PagBank Dynamic Account Module.
 *
 * Copyright © 2024 O2TI. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace O2TI\PagBankDynamicAccount\Model\Console\Command;

use Symfony\Component\Console\Output\OutputInterface;

class AbstractModel
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Output.
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Console Write.
     *
     * @param string $text
     *
     * @return void
     */
    protected function write(string $text)
    {
        if ($this->output instanceof OutputInterface) {
            $this->output->write($text);
        }
    }

    /**
     * Console WriteLn.
     *
     * @param string $text
     *
     * @return void
     */
    protected function writeln($text)
    {
        if ($this->output instanceof OutputInterface) {
            $this->output->writeln($text);
        }
    }
}
