<?php
namespace Application\Entity\Func;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
class Month extends FunctionNode
{
    public $month;
    
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return "MONTH(" . $sqlWalker->walkArithmeticPrimary($this->month) . ")";
    }
    
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
    
        $this->month = $parser->ArithmeticPrimary();
    
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}