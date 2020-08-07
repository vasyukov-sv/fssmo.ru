export function convertMeta (pageData)
{
	let result = {
		title: '',
		meta: []
	};

	if (typeof pageData === 'undefined' || pageData === null)
		return result

	result.title = pageData['title'] || '';

	if (pageData['meta'] !== undefined)
	{
		pageData['meta'].forEach((item) =>
		{
			result.meta.push({
				name: item['name'],
				hid: item['name'],
				content: item['content'],
			});
		});
	}

	return result;
}

export function morph (number, titles)
{
    let cases = [2, 0, 1, 1, 1, 2];

    return titles[ (number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5] ];
}

export function addScript (url)
{
	let script = document.createElement('script');
	script.setAttribute('src', url);

	document.head.appendChild(script);
}