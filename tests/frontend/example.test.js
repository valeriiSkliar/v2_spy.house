import { describe, expect, it, vi } from 'vitest';

describe('Example Test Suite', () => {
  it('should pass basic math test', () => {
    expect(2 + 2).toBe(4);
  });

  it('should test DOM manipulation', () => {
    document.body.innerHTML = '<div id="test">Hello World</div>';
    const element = document.getElementById('test');
    expect(element.textContent).toBe('Hello World');
  });

  it('should test async function', async () => {
    const mockPromise = vi.fn().mockResolvedValue('test result');
    const result = await mockPromise();
    expect(result).toBe('test result');
    expect(mockPromise).toHaveBeenCalledOnce();
  });

  it('should test jQuery mock', () => {
    expect(global.$).toBeDefined();
    expect(global.jQuery).toBeDefined();
  });
});
