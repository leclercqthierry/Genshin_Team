<?php
declare (strict_types = 1);

use GenshinTeam\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Classe de test pour le validateur.
 *
 * @covers \GenshinTeam\Validation\Validator
 */
final class ValidatorTest extends TestCase
{
    /**
     * Vérifie que la validation "required" ajoute une erreur lorsque la valeur est vide.
     *
     * @covers \GenshinTeam\Validation\Validator::validateRequired
     * @return void
     */
    public function testValidateRequiredAddsErrorForEmptyValue(): void
    {
        $validator = new Validator();
        $validator->validateRequired('name', '', 'Le champ est obligatoire');

        $this->assertTrue($validator->hasErrors(), 'Une erreur devrait être enregistrée pour une valeur vide');
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertEquals('Le champ est obligatoire', $errors['name']);
    }

    /**
     * Vérifie que la validation "required" ne génère pas d'erreur lorsque la valeur n'est pas vide.
     *
     * @covers \GenshinTeam\Validation\Validator::validateRequired
     * @return void
     */
    public function testValidateRequiredNoErrorForNonEmptyValue(): void
    {
        $validator = new Validator();
        $validator->validateRequired('name', 'John', 'Le champ est obligatoire');

        $this->assertFalse($validator->hasErrors(), 'Aucune erreur ne devrait être enregistrée pour une valeur non vide');
    }

    /**
     * Vérifie que la validation "email" ajoute une erreur lorsque l'adresse est invalide.
     *
     * @covers \GenshinTeam\Validation\Validator::validateEmail
     * @return void
     */
    public function testValidateEmailAddsErrorForInvalidEmail(): void
    {
        $validator = new Validator();
        $validator->validateEmail('email', 'notanemail', "L'email n'est pas valide");

        $this->assertTrue($validator->hasErrors(), 'Une erreur devrait être enregistrée pour un email invalide');
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals("L'email n'est pas valide", $errors['email']);
    }

    /**
     * Vérifie que la validation "email" ne génère pas d'erreur lorsque l'adresse est valide.
     *
     * @covers \GenshinTeam\Validation\Validator::validateEmail
     * @return void
     */
    public function testValidateEmailNoErrorForValidEmail(): void
    {
        $validator = new Validator();
        $validator->validateEmail('email', 'john@example.com', "L'email n'est pas valide");

        $this->assertFalse($validator->hasErrors(), 'Aucune erreur ne devrait être enregistrée pour un email valide');
    }

    /**
     * Vérifie que la validation "match" ajoute une erreur lorsque les valeurs ne correspondent pas.
     *
     * @covers \GenshinTeam\Validation\Validator::validateMatch
     * @return void
     */
    public function testValidateMatchAddsErrorWhenValuesDiffer(): void
    {
        $validator = new Validator();
        $validator->validateMatch('password', 'secret1', 'secret2', "Les mots de passe ne correspondent pas");

        $this->assertTrue($validator->hasErrors(), 'Une erreur devrait être enregistrée quand les valeurs ne correspondent pas');
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('password', $errors);
        $this->assertEquals("Les mots de passe ne correspondent pas", $errors['password']);
    }

    /**
     * Vérifie que la validation "match" ne génère pas d'erreur lorsque les valeurs correspondent.
     *
     * @covers \GenshinTeam\Validation\Validator::validateMatch
     * @return void
     */
    public function testValidateMatchNoErrorWhenValuesMatch(): void
    {
        $validator = new Validator();
        $validator->validateMatch('password', 'secret', 'secret', "Les mots de passe ne correspondent pas");

        $this->assertFalse($validator->hasErrors(), 'Aucune erreur ne devrait être enregistrée quand les valeurs correspondent');
    }

    /**
     * Vérifie que la validation "minLength" ajoute une erreur lorsque la chaîne est trop courte.
     *
     * @covers \GenshinTeam\Validation\Validator::validateMinLength
     * @return void
     */
    public function testValidateMinLengthAddsErrorWhenTooShort(): void
    {
        $validator = new Validator();
        $validator->validateMinLength('username', 'abc', 5, 'Le pseudo doit contenir au moins 5 caractères');

        $this->assertTrue($validator->hasErrors(), 'Une erreur devrait être enregistrée pour une chaîne trop courte');
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('username', $errors);
        $this->assertEquals('Le pseudo doit contenir au moins 5 caractères', $errors['username']);
    }

    /**
     * Vérifie que la validation "minLength" ne génère pas d'erreur lorsque la chaîne est suffisamment longue.
     *
     * @covers \GenshinTeam\Validation\Validator::validateMinLength
     * @return void
     */
    public function testValidateMinLengthNoErrorWhenLongEnough(): void
    {
        $validator = new Validator();
        $validator->validateMinLength('username', 'abcdef', 5, 'Le pseudo doit contenir au moins 5 caractères');

        $this->assertFalse($validator->hasErrors(), 'Aucune erreur ne devrait être enregistrée pour une chaîne de longueur suffisante');
    }

    /**
     * Vérifie que la validation "pattern" ajoute une erreur lorsque la valeur ne correspond pas à l'expression régulière définie.
     *
     * @covers \GenshinTeam\Validation\Validator::validatePattern
     * @return void
     */
    public function testValidatePatternAddsErrorWhenDoesNotMatch(): void
    {
        $validator = new Validator();
        // On attend que la valeur soit composée uniquement de chiffres
        $pattern = '/^[0-9]+$/';
        $validator->validatePattern('number', 'ABC', $pattern, 'La valeur doit être numérique');

        $this->assertTrue($validator->hasErrors(), 'Une erreur devrait être enregistrée quand la valeur ne correspond pas au pattern');
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('number', $errors);
        $this->assertEquals('La valeur doit être numérique', $errors['number']);
    }

    /**
     * Vérifie que la validation "pattern" ne génère pas d'erreur lorsque la valeur correspond à l'expression régulière définie.
     *
     * @covers \GenshinTeam\Validation\Validator::validatePattern
     * @return void
     */
    public function testValidatePatternNoErrorWhenMatches(): void
    {
        $validator = new Validator();
        $pattern   = '/^[0-9]+$/';
        $validator->validatePattern('number', '12345', $pattern, 'La valeur doit être numérique');

        $this->assertFalse($validator->hasErrors(), 'Aucune erreur ne devrait être enregistrée quand la valeur correspond au pattern');
    }
}
