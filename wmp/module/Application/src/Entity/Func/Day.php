<?php
namespace Application\Entity\Func;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

class Day extends FunctionNode
{
    public $day;
    
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return "DAY(" . $sqlWalker->walkArithmeticPrimary($this->day) . ")";
    }
    
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
    
        $this->day = $parser->ArithmeticPrimary();
    
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}