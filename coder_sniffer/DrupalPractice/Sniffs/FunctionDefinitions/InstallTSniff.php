<?php
/**
 * \DrupalPractice\Sniffs\FunctionDefinitions\InstallTSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\FunctionDefinitions;

use PHP_CodeSniffer\Files\File;
use DrupalPractice\Sniffs\Semantics\FunctionDefinition;
use Coder\Project;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that t() and st() are not used in hook_install() and hook_requirements().
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class InstallTSniff extends FunctionDefinition
{


    /**
     * Process this function definition.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile   The file being scanned.
     * @param int                         $stackPtr    The position of the function name
     *                                                 in the stack.
     * @param int                         $functionPtr The position of the function keyword
     *                                                 in the stack.
     *
     * @return void
     */
    public function processFunction(File $phpcsFile, $stackPtr, $functionPtr)
    {
        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -7));
        // Only check in *.install files.
        if ($fileExtension !== 'install') {
            return;
        }

        $fileName = substr(basename($phpcsFile->getFilename()), 0, -8);
        $tokens   = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== ($fileName.'_install')
            && $tokens[$stackPtr]['content'] !== ($fileName.'_requirements')
        ) {
            return;
        }

        // This check only applies to Drupal 7, not Drupal 8.
        if (Project::getCoreVersion($phpcsFile) !== '7.x') {
            return;
        }

        // Search in the function body for t() calls.
        $string = $phpcsFile->findNext(
            T_STRING,
            $tokens[$functionPtr]['scope_opener'],
            $tokens[$functionPtr]['scope_closer']
        );
        while ($string !== false) {
            if ($tokens[$string]['content'] === 't' || $tokens[$string]['content'] === 'st') {
                $opener = $phpcsFile->findNext(
                    Tokens::$emptyTokens,
                    ($string + 1),
                    null,
                    true
                );
                if ($opener !== false
                    && $tokens[$opener]['code'] === T_OPEN_PARENTHESIS
                ) {
                    $error = 'Do not use t() or st() in installation phase hooks, use $t = get_t() to retrieve the appropriate localization function name';
                    $phpcsFile->addError($error, $string, 'TranslationFound');
                }
            }

            $string = $phpcsFile->findNext(
                T_STRING,
                ($string + 1),
                $tokens[$functionPtr]['scope_closer']
            );
        }//end while

    }//end processFunction()


}//end class
