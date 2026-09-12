<?php
/**
 * The only thing worth asserting about CoreStub in isolation: that it stays
 * substitutable for the real Core, so every `Core` type hint across the
 * codebase accepts it. If the stub stopped extending Core, this fails and
 * explains why the rest of the suite would break.
 *
 * The stub's getters/defaults are deliberately NOT tested here — they are
 * exercised indirectly by every test that uses the stub; asserting them
 * directly would just test the double, not any production behavior.
 */

class CoreStubTest extends TestCase
{
    public function testIsInstanceOfCoreSoTypeHintsHold(): void
    {
        $this->assertInstanceOf(Core::class, new CoreStub(NP_PROJECT_ROOT));
    }
}
