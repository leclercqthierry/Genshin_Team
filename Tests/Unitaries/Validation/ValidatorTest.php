<?php
declare (strict_types = 1);

use GenshinTeam\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Classe de test unitaire pour la classe Validator.
 *
 * Teste l’ensemble des règles de validation disponibles :
 * - required
 * - email
 * - match
 * - minLength
 * - pattern
 *
 * @covers \GenshinTeam\Validation\Validator
 */
final class ValidatorTest extends TestCase
{
    /**
     * Teste que validateRequired enregistre une erreur si la valeur est vide.
     *
     * @return void
     */
    public function testValidateRequiredAddsErrorForEmptyValue(): void
    {
        $validator = new Validator();
        $validator->validateRequired('name', '', 'Le champ est obligatoire');

        $this->assertTrue($validator->hasErrors());

        /** @var array<string, string> $errors */
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertSame('Le champ est obligatoire', $errors['name']);
    }

    /**
     * Teste que validateRequired ne génère pas d’erreur si la valeur est non vide.
     *
     * @return void
     */
    public function testValidateRequiredNoErrorForNonEmptyValue(): void
    {
        $validator = new Validator();
        $validator->validateRequired('name', 'John', 'Le champ est obligatoire');

        $this->assertFalse($validator->hasErrors());
    }

    /**
     * Teste que validateEmail génère une erreur pour une adresse invalide.
     *
     * @return void
     */
    public function testValidateEmailAddsErrorForInvalidEmail(): void
    {
        $validator = new Validator();
        $validator->validateEmail('email', 'notanemail', "L'email n'est pas valide");

        $this->assertTrue($validator->hasErrors());

        /** @var array<string, string> $errors */
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertSame("L'email n'est pas valide", $errors['email']);
    }

    /**
     * Teste que validateEmail ne génère pas d’erreur si l’adresse est correcte.
     *
     * @return void
     */
    public function testValidateEmailNoErrorForValidEmail(): void
    {
        $validator = new Validator();
        $validator->validateEmail('email', 'john@example.com', "L'email n'est pas valide");

        $this->assertFalse($validator->hasErrors());
    }

    /**
     * Teste que validateMatch ajoute une erreur si les valeurs ne correspondent pas.
     *
     * @return void
     */
    public function testValidateMatchAddsErrorWhenValuesDiffer(): void
    {
        $validator = new Validator();
        $validator->validateMatch('password', 'secret1', 'secret2', "Les mots de passe ne correspondent pas");

        $this->assertTrue($validator->hasErrors());

        /** @var array<string, string> $errors */
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('password', $errors);
        $this->assertSame("Les mots de passe ne correspondent pas", $errors['password']);
    }

    /**
     * Teste que validateMatch ne génère pas d’erreur si les valeurs sont identiques.
     *
     * @return void
     */
    public function testValidateMatchNoErrorWhenValuesMatch(): void
    {
        $validator = new Validator();
        $validator->validateMatch('password', 'secret', 'secret', "Les mots de passe ne correspondent pas");

        $this->assertFalse($validator->hasErrors());
    }

    /**
     * Teste que validateMinLength ajoute une erreur si la chaîne est trop courte.
     *
     * @return void
     */
    public function testValidateMinLengthAddsErrorWhenTooShort(): void
    {
        $validator = new Validator();
        $validator->validateMinLength('username', 'abc', 5, 'Le pseudo doit contenir au moins 5 caractères');

        $this->assertTrue($validator->hasErrors());

        /** @var array<string, string> $errors */
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('username', $errors);
        $this->assertSame('Le pseudo doit contenir au moins 5 caractères', $errors['username']);
    }

    /**
     * Teste que validateMinLength ne génère pas d’erreur si la chaîne est assez longue.
     *
     * @return void
     */
    public function testValidateMinLengthNoErrorWhenLongEnough(): void
    {
        $validator = new Validator();
        $validator->validateMinLength('username', 'abcdef', 5, 'Le pseudo doit contenir au moins 5 caractères');

        $this->assertFalse($validator->hasErrors());
    }

    /**
     * Teste que validatePattern ajoute une erreur si la chaîne ne correspond pas au pattern.
     *
     * @return void
     */
    public function testValidatePatternAddsErrorWhenDoesNotMatch(): void
    {
        $validator = new Validator();
        $pattern   = '/^[0-9]+$/';
        $validator->validatePattern('number', 'ABC', $pattern, 'La valeur doit être numérique');

        $this->assertTrue($validator->hasErrors());

        /** @var array<string, string> $errors */
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('number', $errors);
        $this->assertSame('La valeur doit être numérique', $errors['number']);
    }

    /**
     * Teste que validatePattern ne génère pas d’erreur si la valeur est valide.
     *
     * @return void
     */
    public function testValidatePatternNoErrorWhenMatches(): void
    {
        $validator = new Validator();
        $pattern   = '/^[0-9]+$/';
        $validator->validatePattern('number', '12345', $pattern, 'La valeur doit être numérique');

        $this->assertFalse($validator->hasErrors());
    }
    /**
     * Teste que setError ajoute une erreur personnalisée.
     *
     * @return void
     */
    public function testSetErrorAddsError(): void
    {
        $validator = new Validator();
        $validator->setError('foo', 'Erreur personnalisée');
        $this->assertTrue($validator->hasErrors());
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('foo', $errors);
        $this->assertSame('Erreur personnalisée', $errors['foo']);
    }
}
