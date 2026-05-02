import { workflow, trigger, node, expr, ifElse, sticky, newCredential } from '@n8n/workflow-sdk';

const webhookTrigger = trigger({
	type: 'n8n-nodes-base.webhook',
	version: 2.1,
	credentials: {
		httpHeaderAuth: newCredential('Eklektika site X-Sync-Token'),
	},
	config: {
		name: 'Webhook',
		parameters: {
			httpMethod: 'POST',
			path: '9cdcd623-305c-4da3-877d-3c9b7a05bd0a',
			responseMode: 'responseNode',
			authentication: 'headerAuth',
			options: {},
		},
		position: [0, 200],
	},
	output: [
		{
			body: {
				METHOD: 'crm.contact.list',
				PARAMS: { filter: { EMAIL: 'x@y.ru' }, select: ['ID'], start: '-1' },
			},
			headers: { 'x-sync-token': 'test-token' },
		},
	],
});

const setParse = node({
	type: 'n8n-nodes-base.set',
	version: 3.4,
	config: {
		name: 'Parse CRM proxy input',
		parameters: {
			mode: 'manual',
			assignments: {
				assignments: [
					{
						id: 'a-method',
						name: 'crmMethod',
						value: expr(
							'={{ ($json.body && $json.body.METHOD) ? $json.body.METHOD : (($json.body && $json.body.method) ? $json.body.method : "") }}',
						),
						type: 'string',
					},
					{
						id: 'a-params',
						name: 'crmParams',
						value: expr(
							'={{ ($json.body && $json.body.PARAMS !== undefined) ? $json.body.PARAMS : (($json.body && $json.body.params !== undefined) ? $json.body.params : {}) }}',
						),
						type: 'object',
					},
				],
			},
			includeOtherFields: false,
		},
		position: [240, 200],
	},
	output: [{ crmMethod: 'crm.contact.list', crmParams: {} }],
});

const ifMethod = ifElse({
	version: 2.2,
	config: {
		name: 'METHOD задан?',
		parameters: {
			conditions: {
				combinator: 'and',
				options: {
					version: 2,
					caseSensitive: true,
					leftValue: '',
					typeValidation: 'strict',
				},
				conditions: [
					{
						id: 'c-method',
						leftValue: '={{ $json.crmMethod }}',
						rightValue: '',
						operator: { type: 'string', operation: 'notEmpty' },
					},
				],
			},
		},
		position: [480, 200],
	},
});

const setErrNoMethod = node({
	type: 'n8n-nodes-base.set',
	version: 3.4,
	config: {
		name: 'Ошибка: нет METHOD',
		parameters: {
			mode: 'manual',
			assignments: {
				assignments: [
					{ id: 'e1', name: 'success', value: 0, type: 'number' },
					{ id: 'e2', name: 'error', value: 'missing_method', type: 'string' },
				],
			},
			includeOtherFields: false,
		},
		position: [720, 380],
	},
	output: [{ success: 0, error: 'missing_method' }],
});

const ifPrefix = ifElse({
	version: 2.2,
	config: {
		name: 'Есть EKLEKTIKA_B24_REST_PREFIX?',
		parameters: {
			conditions: {
				combinator: 'and',
				options: {
					version: 2,
					caseSensitive: true,
					leftValue: '',
					typeValidation: 'strict',
				},
				conditions: [
					{
						id: 'c-prefix',
						leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
						rightValue: '',
						operator: { type: 'string', operation: 'notEmpty' },
					},
				],
			},
		},
		position: [720, 120],
	},
});

const setErrPrefix = node({
	type: 'n8n-nodes-base.set',
	version: 3.4,
	config: {
		name: 'Ошибка: нет префикса B24',
		parameters: {
			mode: 'manual',
			assignments: {
				assignments: [
					{ id: 'p1', name: 'success', value: 0, type: 'number' },
					{ id: 'p2', name: 'error', value: 'config', type: 'string' },
					{
						id: 'p3',
						name: 'message',
						value: 'EKLEKTIKA_B24_REST_PREFIX is empty',
						type: 'string',
					},
				],
			},
			includeOtherFields: false,
		},
		position: [960, 280],
	},
	output: [{ success: 0, error: 'config' }],
});

const setCrmUrl = node({
	type: 'n8n-nodes-base.set',
	version: 3.4,
	config: {
		name: 'Собрать URL метода CRM',
		parameters: {
			mode: 'manual',
			assignments: {
				assignments: [
					{
						id: 'u1',
						name: 'crmUrl',
						value: expr(
							'={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
						),
						type: 'string',
					},
				],
			},
			includeOtherFields: true,
		},
		position: [960, 80],
	},
	output: [{ crmMethod: 'crm.contact.list', crmParams: {}, crmUrl: 'https://x/rest/1/t/crm.contact.list.json' }],
});

const flattenFormJs =
	'function flatten(obj, prefix) {\n' +
	'  const pairs = [];\n' +
	'  if (obj === null || obj === undefined) return pairs;\n' +
	"  const p = prefix || '';\n" +
	"  if (typeof obj !== 'object') {\n" +
	'    pairs.push([p, obj]);\n' +
	'    return pairs;\n' +
	'  }\n' +
	'  if (Array.isArray(obj)) {\n' +
	'    for (let i = 0; i < obj.length; i++) {\n' +
	"      const key = p ? p + '[' + i + ']' : '[' + i + ']';\n" +
	'      pairs.push(...flatten(obj[i], key));\n' +
	'    }\n' +
	'    return pairs;\n' +
	'  }\n' +
	'  for (const key of Object.keys(obj)) {\n' +
	"    const next = p ? p + '[' + key + ']' : key;\n" +
	'    pairs.push(...flatten(obj[key], next));\n' +
	'  }\n' +
	'  return pairs;\n' +
	'}\n' +
	'const j = $input.first().json;\n' +
	'const pairs = flatten(j.crmParams || {});\n' +
	"const delim = String.fromCharCode(38);\n" +
	'let httpBody = \'\';\n' +
	'for (let i = 0; i < pairs.length; i++) {\n' +
	'  const kv = pairs[i];\n' +
	"  const part = encodeURIComponent(kv[0]) + '=' + encodeURIComponent(kv[1] === null || kv[1] === undefined ? '' : String(kv[1]));\n" +
	'  httpBody = i === 0 ? part : httpBody + delim + part;\n' +
	'}\n' +
	'return [{ json: { url: j.crmUrl, httpBody: httpBody } }];';

const flattenForm = node({
	type: 'n8n-nodes-base.code',
	version: 2,
	config: {
		name: 'PARAMS → form-urlencoded',
		parameters: {
			mode: 'runOnceForAllItems',
			language: 'javaScript',
			jsCode: flattenFormJs,
		},
		position: [1180, 80],
	},
	output: [{ json: { url: 'https://x/a.json', httpBody: 'a=1' } }],
});

const httpCrm = node({
	type: 'n8n-nodes-base.httpRequest',
	version: 4.4,
	config: {
		name: 'POST Bitrix24 REST',
		parameters: {
			method: 'POST',
			url: expr('={{ $json.url }}'),
			sendBody: true,
			contentType: 'raw',
			body: expr('={{ $json.httpBody }}'),
			rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
			options: {
				response: {
					response: {
						responseFormat: 'json',
						neverError: true,
						fullResponse: false,
					},
				},
			},
		},
		position: [1400, 80],
	},
	output: [{ result: {} }],
});

const shapeSiteJs =
	'const b = $input.first().json;\n' +
	"if (b && typeof b === 'object' && b.error) {\n" +
	'  return [{\n' +
	'    json: {\n' +
	'      success: 0,\n' +
	'      error: String(b.error),\n' +
	"      error_description: b.error_description ? String(b.error_description) : '',\n" +
	'    },\n' +
	'  }];\n' +
	'}\n' +
	"if (b && typeof b === 'object' && Object.prototype.hasOwnProperty.call(b, 'result')) {\n" +
	'  return [{ json: { success: 1, result: b.result } }];\n' +
	'}\n' +
	"return [{ json: { success: 0, error: 'unexpected_b24_shape', transport_response: b } }];";

const shapeSite = node({
	type: 'n8n-nodes-base.code',
	version: 2,
	config: {
		name: 'Ответ в контракт сайта',
		parameters: {
			mode: 'runOnceForAllItems',
			language: 'javaScript',
			jsCode: shapeSiteJs,
		},
		position: [1620, 80],
	},
	output: [{ json: { success: 1, result: {} } }],
});

const respond = node({
	type: 'n8n-nodes-base.respondToWebhook',
	version: 1.5,
	config: {
		name: 'Respond JSON',
		parameters: {
			respondWith: 'json',
			responseBody: expr('={{ $json }}'),
			options: {
				responseCode: 200,
			},
		},
		position: [1840, 200],
	},
});

sticky(
	'**Штатные узлы:** Parse (Set) → проверки (IF) → URL (Set) → **HTTP Request** → ответ.\n**Один узел Code** — только flatten вложенных `PARAMS` в строку `x-www-form-urlencoded` (как `http_build_query` в PHP); штатный HTTP Request не строит такие ключи из произвольного JSON.\n**Второй короткий Code** — привести JSON Bitrix к `{ success, result }` (ветвление по полю `result` vs `error` в IF выражениях получается хрупким).\nWebhook: Header Auth `X-Sync-Token` = `inbound_secret`.',
	[setParse, httpCrm],
	{ color: 5 },
);

export default workflow('site-to-crm-rest-bridge', 'Site to CRM')
	.add(webhookTrigger)
	.to(
		setParse.to(
			ifMethod
				.onTrue(
					ifPrefix
						.onTrue(setCrmUrl.to(flattenForm.to(httpCrm.to(shapeSite.to(respond)))))
						.onFalse(setErrPrefix.to(respond)),
				)
				.onFalse(setErrNoMethod.to(respond)),
		),
	);
