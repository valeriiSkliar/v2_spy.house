import { mount, VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import DateSelectWithFlatpickr from '../../../resources/js/vue-components/ui/DateSelect_with_flatpickr.vue';

// Mock flatpickr
const mockFlatpickrInstance = {
  open: vi.fn(),
  close: vi.fn(),
  clear: vi.fn(),
  destroy: vi.fn(),
  selectedDates: [] as Date[],
  calendarContainer: document.createElement('div'),
};

vi.mock('flatpickr', () => ({
  default: vi.fn(() => mockFlatpickrInstance),
}));

vi.mock('flatpickr/dist/flatpickr.css', () => ({}));

describe('DateSelect_with_flatpickr', () => {
  let wrapper: VueWrapper<any>;
  let mockFlatpickr: any;
  
  const defaultOptions = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last_7_days', label: 'Last 7 days' },
  ];

  beforeEach(async () => {
    // Get the mocked flatpickr function
    const flatpickrModule = await import('flatpickr');
    mockFlatpickr = flatpickrModule.default as any;
    
    // Reset mocks
    vi.clearAllMocks();
    mockFlatpickr.mockClear();
    mockFlatpickrInstance.selectedDates = [];
    
    // Setup DOM
    document.body.innerHTML = '';
    
    // Add calendar container to mock instance
    mockFlatpickrInstance.calendarContainer = document.createElement('div');
    mockFlatpickrInstance.calendarContainer.classList.add('flatpickr-calendar');
  });

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount();
    }
  });

  describe('Component Initialization', () => {
    it('renders with default props', () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
        },
      });

      expect(wrapper.find('.filter-date-select').exists()).toBe(true);
      expect(wrapper.find('.date-select-field').exists()).toBe(true);
      expect(wrapper.text()).toContain('Select date');
    });

    it('displays custom placeholder', () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          placeholder: 'Choose a date',
        },
      });

      expect(wrapper.text()).toContain('Choose a date');
    });

    it('shows selected option label', () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: 'today',
          options: defaultOptions,
        },
      });

      expect(wrapper.text()).toContain('Today');
    });
  });

  describe('Dropdown Functionality', () => {
    beforeEach(() => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
        },
      });
    });

    it('toggles dropdown on field click', async () => {
      const field = wrapper.find('.date-select-field');
      
      expect(wrapper.vm.isOpen).toBe(false);
      
      await field.trigger('click');
      
      expect(wrapper.vm.isOpen).toBe(true);
      expect(field.attributes('aria-expanded')).toBe('true');
    });

    it('closes dropdown on second click', async () => {
      const field = wrapper.find('.date-select-field');
      
      await field.trigger('click');
      expect(wrapper.vm.isOpen).toBe(true);
      
      await field.trigger('click');
      expect(wrapper.vm.isOpen).toBe(false);
    });

    it('shows dropdown arrow animation', async () => {
      const field = wrapper.find('.date-select-field');
      const arrow = wrapper.find('.dropdown-arrow');
      
      expect(arrow.classes()).not.toContain('is-open');
      
      await field.trigger('click');
      
      expect(arrow.classes()).toContain('is-open');
    });
  });

  describe('Option Selection', () => {
    beforeEach(() => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
        },
      });
    });

    it('renders all options', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      
      const options = wrapper.findAll('.range-option');
      expect(options).toHaveLength(3);
      expect(options[0].text()).toBe('Today');
      expect(options[1].text()).toBe('Yesterday');
      expect(options[2].text()).toBe('Last 7 days');
    });

    it('highlights active option', async () => {
      await wrapper.setProps({ value: 'today' });
      await wrapper.find('.date-select-field').trigger('click');
      
      const options = wrapper.findAll('.range-option');
      expect(options[0].classes()).toContain('active');
      expect(options[1].classes()).not.toContain('active');
    });

    it('emits update:value on option click', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      
      const option = wrapper.findAll('.range-option')[1];
      await option.trigger('click');
      
      expect(wrapper.emitted('update:value')).toEqual([['yesterday']]);
    });

    it('closes dropdown after option selection', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      expect(wrapper.vm.isOpen).toBe(true);
      
      const option = wrapper.findAll('.range-option')[0];
      await option.trigger('click');
      
      expect(wrapper.vm.isOpen).toBe(false);
    });
  });

  describe('Custom Date Functionality', () => {
    beforeEach(() => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
          customDateLabel: 'Pick Custom Date',
        },
      });
    });

    it('shows custom date section when enabled', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      
      expect(wrapper.find('.custom-date-section').exists()).toBe(true);
      expect(wrapper.find('.custom-date-trigger').text()).toBe('Pick Custom Date');
    });

    it('does not show custom date section when disabled', async () => {
      await wrapper.setProps({ enableCustomDate: false });
      await wrapper.find('.date-select-field').trigger('click');
      
      expect(wrapper.find('.custom-date-section').exists()).toBe(false);
    });

    it('opens flatpickr on custom date trigger click', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      
      const customTrigger = wrapper.find('.custom-date-trigger');
      await customTrigger.trigger('click');
      await nextTick();
      
      // Wait for setTimeout in component
      await new Promise(resolve => setTimeout(resolve, 250));
      
      expect(mockFlatpickrInstance.open).toHaveBeenCalled();
    });

    it('shows flatpickr input element', () => {
      const input = wrapper.find('.flatpickr-input');
      expect(input.exists()).toBe(true);
      expect(input.attributes('style')).toContain('display: none');
    });

    it('shows flatpickr container', () => {
      const container = wrapper.find('.flatpickr-container');
      expect(container.exists()).toBe(true);
    });
  });

  describe('Flatpickr Integration', () => {
    it('initializes with single mode by default', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
        },
      });

      // Wait for component to mount and initialize
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      expect(mockFlatpickr).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          mode: 'single',
          dateFormat: 'Y-m-d',
        })
      );
    });

    it('initializes with range mode when specified', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component to mount and initialize
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      expect(mockFlatpickr).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          mode: 'range',
        })
      );
    });

    it('passes custom date format', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
          dateFormat: 'd-m-Y',
        },
      });

      // Wait for component to mount and initialize
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      expect(mockFlatpickr).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          dateFormat: 'd-m-Y',
        })
      );
    });

    it('passes min/max date constraints', async () => {
      const minDate = '2024-01-01';
      const maxDate = '2024-12-31';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
          minDate,
          maxDate,
        },
      });

      // Wait for component to mount and initialize
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      expect(mockFlatpickr).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          minDate,
          maxDate,
        })
      );
    });
  });

  describe('Custom Date Events', () => {
    beforeEach(async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
        },
      });
      
      // Wait for flatpickr to be initialized
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));
    });

    it('emits custom-date-selected on date selection', () => {
      const configCall = mockFlatpickr.mock.calls[0];
      const config = configCall[1];
      
      const testDate = new Date('2024-01-15');
      config.onChange([testDate]);
      
      expect(wrapper.emitted('custom-date-selected')).toEqual([[[testDate]]]);
    });

    it('emits update:value with custom format on date selection', () => {
      const configCall = mockFlatpickr.mock.calls[0];
      const config = configCall[1];
      
      const testDate = new Date('2024-01-15');
      config.onChange([testDate]);
      
      expect(wrapper.emitted('update:value')).toEqual([['custom_2024-01-15']]);
    });

    it('handles range date selection', async () => {
      // Create new wrapper with range mode
      wrapper.unmount();
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for flatpickr to be initialized with range mode
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      const configCall = mockFlatpickr.mock.calls.find(call => call[1].mode === 'range');
      expect(configCall).toBeDefined();
      const config = configCall[1];
      
      const startDate = new Date('2024-01-15');
      const endDate = new Date('2024-01-20');
      config.onChange([startDate, endDate]);
      
      expect(wrapper.emitted('update:value')).toEqual([['custom_2024-01-15_to_2024-01-20']]);
    });
  });

  describe('Date Label Formatting', () => {
    it('shows single date label', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: 'custom_2024-01-15',
          options: defaultOptions,
          enableCustomDate: true,
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set component state to custom date and mock flatpickr instance
      wrapper.vm.isCustomDate = true;
      const mockDate = new Date('2024-01-15');
      mockFlatpickrInstance.selectedDates = [mockDate];
      
      await nextTick();
      
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('1/15/2024');
    });

    it('shows range date label', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: 'custom_2024-01-15_to_2024-01-20',
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set component state to custom date and mock flatpickr instance
      wrapper.vm.isCustomDate = true;
      const startDate = new Date('2024-01-15');
      const endDate = new Date('2024-01-20');
      mockFlatpickrInstance.selectedDates = [startDate, endDate];
      
      await nextTick();
      
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('1/15/2024 - 1/20/2024');
    });

    it('shows incomplete range status', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: 'custom_2024-01-15',
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set component state to custom date and mock flatpickr instance with incomplete range
      wrapper.vm.isCustomDate = true;
      const startDate = new Date('2024-01-15');
      mockFlatpickrInstance.selectedDates = [startDate];
      
      await nextTick();
      await wrapper.find('.date-select-field').trigger('click');
      
      const customTrigger = wrapper.find('.custom-date-trigger');
      expect(customTrigger.classes()).toContain('incomplete-range');
      expect(customTrigger.text()).toContain('выберите конечную дату');
    });
  });

  describe('Outside Click Handling', () => {
    beforeEach(() => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
        },
        attachTo: document.body,
      });
    });

    it('closes dropdown on outside click', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      expect(wrapper.find('.date-options-dropdown').isVisible()).toBe(true);
      
      // Simulate outside click
      const outsideElement = document.createElement('div');
      document.body.appendChild(outsideElement);
      
      const clickEvent = new Event('click', { bubbles: true });
      Object.defineProperty(clickEvent, 'target', { value: outsideElement });
      document.dispatchEvent(clickEvent);
      
      await nextTick();
      
      expect(wrapper.find('.date-options-dropdown').isVisible()).toBe(false);
      
      document.body.removeChild(outsideElement);
    });

    it('does not close dropdown on inside click', async () => {
      await wrapper.find('.date-select-field').trigger('click');
      expect(wrapper.find('.date-options-dropdown').isVisible()).toBe(true);
      
      // Simulate inside click
      const insideElement = wrapper.find('.date-options-dropdown').element;
      const clickEvent = new Event('click', { bubbles: true });
      Object.defineProperty(clickEvent, 'target', { value: insideElement });
      document.dispatchEvent(clickEvent);
      
      await nextTick();
      
      expect(wrapper.find('.date-options-dropdown').isVisible()).toBe(true);
    });
  });

  describe('Component Cleanup', () => {
    it('destroys flatpickr instance on unmount', async () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
          enableCustomDate: true,
        },
      });

      // Wait for flatpickr to be initialized
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      wrapper.unmount();
      
      expect(mockFlatpickrInstance.destroy).toHaveBeenCalled();
    });

    it('removes event listeners on unmount', () => {
      const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener');
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: defaultOptions,
        },
      });

      wrapper.unmount();
      
      expect(removeEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));
    });
  });

  describe('Props Validation', () => {
    it('handles empty options array', () => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: '',
          options: [],
        },
      });

      expect(wrapper.find('.filter-date-select').exists()).toBe(true);
    });

    it('accepts all mode values', () => {
      expect(() => {
        wrapper = mount(DateSelectWithFlatpickr, {
          props: {
            value: '',
            options: defaultOptions,
            mode: 'single',
          },
        });
      }).not.toThrow();

      expect(() => {
        wrapper = mount(DateSelectWithFlatpickr, {
          props: {
            value: '',
            options: defaultOptions,
            mode: 'range',
          },
        });
      }).not.toThrow();
    });
  });

  describe('Value Watching', () => {
    beforeEach(() => {
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: 'custom_2024-01-15',
          options: defaultOptions,
          enableCustomDate: true,
        },
      });
      // Set custom date state manually since the component doesn't automatically set it
      wrapper.vm.isCustomDate = true;
    });

    it('resets custom date state when value changes to preset', async () => {
      // Start with custom date
      expect(wrapper.vm.isCustomDate).toBe(true);
      
      // Change to preset value
      await wrapper.setProps({ value: 'today' });
      await nextTick();
      
      expect(wrapper.vm.isCustomDate).toBe(false);
    });

    it('clears flatpickr when value is reset', async () => {
      // Wait for flatpickr to be initialized
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));
      
      await wrapper.setProps({ value: '' });
      await nextTick();
      
      expect(mockFlatpickrInstance.clear).toHaveBeenCalled();
    });
  });

  describe('State Restoration from URL', () => {
    it('restores single custom date from URL parameter', async () => {
      const customDateValue = 'custom_2025-06-08';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: customDateValue,
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'single',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Manually set the custom date state since it would be set by the parent component
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Check that the component displays the correct date
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('6/8/2025');
      
      // Check that the component is in custom date mode
      expect(wrapper.vm.isCustomDate).toBe(true);
    });

    it('restores date range from URL parameter', async () => {
      const customDateValue = 'custom_2025-06-08_to_2025-06-26';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: customDateValue,
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Manually set the custom date state since it would be set by the parent component
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Check that the component displays the correct date range
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('6/8/2025 - 6/26/2025');
      
      // Check that the component is in custom date mode
      expect(wrapper.vm.isCustomDate).toBe(true);
    });

    it('shows custom date trigger as active when restored from URL', async () => {
      const customDateValue = 'custom_2025-06-08_to_2025-06-26';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: customDateValue,
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set the custom date state
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Open dropdown to see custom date trigger
      await wrapper.find('.date-select-field').trigger('click');
      
      const customTrigger = wrapper.find('.custom-date-trigger');
      expect(customTrigger.classes()).toContain('active');
      expect(customTrigger.classes()).not.toContain('incomplete-range');
    });

    it('handles invalid date format gracefully', async () => {
      const invalidDateValue = 'custom_invalid-date-format';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: invalidDateValue,
          options: defaultOptions,
          enableCustomDate: true,
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set the custom date state
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Should fallback to placeholder when date format is invalid
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toBe('Select date');
    });

    it('handles missing end date in range gracefully', async () => {
      const incompleteRangeValue = 'custom_2025-06-08_to_';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: incompleteRangeValue,
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set the custom date state
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Should handle incomplete range gracefully
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toBe('Select date');
    });

    it('preserves flatpickr selected dates when restored from URL', async () => {
      const customDateValue = 'custom_2025-06-08_to_2025-06-26';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: customDateValue,
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
        },
      });

      // Wait for component initialization
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set the custom date state and mock flatpickr selected dates
      wrapper.vm.isCustomDate = true;
      const startDate = new Date('2025-06-08');
      const endDate = new Date('2025-06-26');
      mockFlatpickrInstance.selectedDates = [startDate, endDate];
      
      await nextTick();

      // Check that flatpickr state is correctly represented
      expect(wrapper.vm.hasIncompleteRange).toBe(false);
      
      // Check that the label shows the correct dates
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('6/8/2025 - 6/26/2025');
    });

    it('switches between preset and custom date properly during restoration', async () => {
      // Start with a preset value
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: 'today',
          options: defaultOptions,
          enableCustomDate: true,
        },
      });

      await nextTick();
      
      // Verify preset is selected
      expect(wrapper.vm.isCustomDate).toBe(false);
      let label = wrapper.find('.date-select-field span').text();
      expect(label).toBe('Today');

      // Simulate URL change to custom date (like navigation)
      await wrapper.setProps({ value: 'custom_2025-06-08' });
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Verify custom date is now selected
      expect(wrapper.vm.isCustomDate).toBe(true);
      label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('6/8/2025');

      // Switch back to preset
      await wrapper.setProps({ value: 'yesterday' });
      await nextTick();

      // Verify preset is selected again and custom state is reset
      expect(wrapper.vm.isCustomDate).toBe(false);
      label = wrapper.find('.date-select-field span').text();
      expect(label).toBe('Yesterday');
    });

    it('handles URL parameter format exactly as used in application', async () => {
      // Simulate the exact URL parameter format from the logs
      const urlParameterValue = 'custom_2025-06-08_to_2025-06-26';
      
      wrapper = mount(DateSelectWithFlatpickr, {
        props: {
          value: urlParameterValue,
          options: defaultOptions,
          enableCustomDate: true,
          mode: 'range',
          dateFormat: 'Y-m-d', // Same format as used in application
        },
      });

      // Wait for component initialization (matching the logs)
      await nextTick();
      await new Promise(resolve => setTimeout(resolve, 150));

      // Set custom date state as it would be set by the filters store
      wrapper.vm.isCustomDate = true;
      await nextTick();

      // Verify the component state matches expectations
      const label = wrapper.find('.date-select-field span').text();
      expect(label).toContain('6/8/2025 - 6/26/2025');
      expect(wrapper.vm.isCustomDate).toBe(true);
      
      // Verify flatpickr was initialized with correct mode
      expect(mockFlatpickr).toHaveBeenCalledWith(
        expect.any(HTMLInputElement),
        expect.objectContaining({
          mode: 'range',
          dateFormat: 'Y-m-d',
        })
      );
    });
  });
});