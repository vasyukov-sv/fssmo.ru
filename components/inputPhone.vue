<template>
	<div class="tel-input-container">
		<input type="tel" ref="input" @input.prevent v-bind="$attrs">
	</div>
</template>

<script>
	import 'intl-tel-input/build/css/intlTelInput.css'

	export default {
		inheritAttrs: false,
		name: 'input-phone',
		props: {
			value: {
				type: String,
				default: ''
			}
		},
		data () {
			return {
				phone: null
			}
		},
		watch: {
			value (v) {
				this.phone.setNumber(this.format(v))
			}
		},
		methods: {
			format (val)
			{
				let rep = {
					' ': '',
					'(': '',
					')': '',
					'-': '',
				};

				for (let key in rep) {
					val = val.split(key).join(rep[key])
				}

				return val
			},
			change ()
			{
				let number = this.format(this.phone.getNumber(1))

				if (this.value === number)
					return

				this.$emit('input', number)
			}
		},
		mounted ()
		{
			let input = this.$refs['input'];
			let intlTelInput = require('intl-tel-input');

			window.intlTelInputGlobals.windowLoaded = true

			this.phone = intlTelInput(input, {
				allowDropdown: true,
				formatOnDisplay: true,
				initialCountry: "ru",
				nationalMode: true,
				onlyCountries: ['az', 'am', 'by', 'kz', 'md', 'ru'],
				separateDialCode: true,
				utilsScript: '/js/libphonenumber.js'
			});

			this.phone.promise.then(() =>
			{
				if (this.value && this.value.length > 0)
					this.phone.setNumber(this.format(this.value));
			});

			input.addEventListener('change', this.change);
			input.addEventListener('countrychange', this.change);
			input.addEventListener('keyup', this.change);
		},
		destroy () {
			this.phone.destroy();
		}
	}
</script>