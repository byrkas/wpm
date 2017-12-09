<?php
namespace Application\Entity\Func;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

class Age extends FunctionNode
{
    public $date;
    
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return "TIMESTAMPDIFF(YEAR," . $sqlWalker->walkArithmeticPrimary($this->date) . ",CURDATE())";
    }
    
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
    
        $this->date = $parser->ArithmeticPrimary();
    
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}