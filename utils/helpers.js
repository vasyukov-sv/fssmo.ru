export function getHost ()
{
	if (typeof window === 'undefined')
		return ''

	return window.location.protocol + "//"
		+ window.location.hostname
		+ (window.location.port ? ':' + window.location.port : '')
}

export function objectEqual (obj1, obj2) {
	return JSON.stringify(obj1) === JSON.stringify(obj2)
}

export function paymentHandler (result)
{
	if (typeof result['payment'] !== "undefined" && typeof result['payment'] === "object")
	{
		if (typeof result['payment']['template'] !== "undefined" && result['payment']['template'].length > 0)
		{
			let form = document.createElement('div')
			form.id = 'buyEventForm'
			form.innerHTML = result['payment']['template']

			document.querySelector('body').appendChild(form)

			let payForm = document.querySelector('#buyEventForm')

			if (payForm)
			{
				if (payForm.querySelector('form'))
					payForm.querySelector('form').submit()
				else if (payForm.querySelector('a'))
					window.location.href = payForm.querySelector('a').getAttribute('href')
				else
					throw new Error('Произошла ошибка при инициализации платежа, попробуйте повторить попытку позднее')
			}
		}
		else if (typeof result['payment']['redirect'] !== "undefined" && result['payment']['redirect'].length > 0)
			window.location.href = result['payment']['redirect'];
		else
			throw new Error('Произошла ошибка при инициализации платежа, попробуйте повторить попытку позднее')
	}
}