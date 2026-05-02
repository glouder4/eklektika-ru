import { workflow, trigger, node, ifElse, newCredential, sticky } from '@n8n/workflow-sdk';

const headerAuthCred = {
  credentials: {
    httpHeaderAuth: newCredential('Site CRM Outbound Header'),
  },
};

const flatEnc =
  'function flatten(obj,prefix){const pairs=[];if(obj===null||obj===undefined)return pairs;const p=prefix||"";if(typeof obj!=="object"){pairs.push([p,obj]);return pairs;}if(Array.isArray(obj)){for(let i=0;i<obj.length;i++){const key=p?p+"["+i+"]":"["+i+"]";pairs.push(...flatten(obj[i],key));}return pairs;}for(const key of Object.keys(obj)){const next=p?p+"["+key+"]":key;pairs.push(...flatten(obj[key],next));}return pairs;}const j=$input.first().json;const pairs=flatten(j.crmParams||{});const delim=String.fromCharCode(38);let httpBody="";for(let i=0;i<pairs.length;i++){const kv=pairs[i];const part=encodeURIComponent(kv[0])+"="+encodeURIComponent(kv[1]===null||kv[1]===undefined?"":String(kv[1]));httpBody=i===0?part:httpBody+delim+part;}return[{json:{url:j.crmUrl,httpBody}}];';

const shapeEnc =
  'const b=$input.first().json;if(b&&typeof b==="object"&&b.error){return[{json:{success:0,error:String(b.error),error_description:b.error_description?String(b.error_description):""}}];}if(b&&typeof b==="object"&&Object.prototype.hasOwnProperty.call(b,"result")){return[{json:{success:1,result:b.result}}];}return[{json:{success:0,error:"unexpected_b24_shape",transport_response:b}}];';

const whProx = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH legacy',
    parameters: {
      httpMethod: 'POST',
      path: '9cdcd623-305c-4da3-877d-3c9b7a05bd0a',
      responseMode: 'responseNode',
      authentication: 'headerAuth',
      options: {},
    },
    position: [0, 240],
    ...headerAuthCred,
  },
  output: [{ body: { METHOD: 'crm.contact.list', PARAMS: {} }, headers: {} }],
});

const parseProxy = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'Parse proxy',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'a1',
            name: 'crmMethod',
            value: '={{ ($json.body && $json.body.METHOD) ? $json.body.METHOD : (($json.body && $json.body.method) ? $json.body.method : "") }}',
            type: 'string',
          },
          {
            id: 'a2',
            name: 'crmParams',
            value:
              '={{ ($json.body && $json.body.PARAMS !== undefined) ? $json.body.PARAMS : (($json.body && $json.body.params !== undefined) ? $json.body.params : {}) }}',
            type: 'object',
          },
        ],
      },
      includeOtherFields: false,
    },
    position: [260, 240],
  },
  output: [{ crmMethod: '', crmParams: {} }],
});

const whU = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH uniq',
    parameters: {
      httpMethod: 'POST',
      path: 'registration/crm-check-unique-contact-v1',
      responseMode: 'responseNode',
      authentication: 'headerAuth',
      options: {},
    },
    position: [0, 0],
    ...headerAuthCred,
  },
  output: [{ body: { EMAIL: 'x@y.ru', PERSONAL_PHONE: '+7' }, headers: {} }],
});

const prepU = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Map uniq',
    parameters: {
      mode: 'runOnceForAllItems',
      language: 'javaScript',
      jsCode:
        "function fb(i){const j=i.json||{};return j.body!==undefined&&j.body!==null?j.body:j}const r=fb($input.first());const em=r.EMAIL||r.email||'';const ph=r.PHONE||r.PERSONAL_PHONE||r.phone||'';const f={};if(em!=''){f['=EMAIL']=String(em)}if(ph!=''){f['=PHONE']=String(ph)}return[{json:{crmMethod:'crm.contact.list',crmParams:{select:['ID','NAME','LAST_NAME','SECOND_NAME','EMAIL','PHONE'],order:{ID:'ASC'},filter:f}}}];",
    },
    position: [260, 0],
    executeOnce: true,
  },
  output: [{ crmMethod: '', crmParams: {} }],
});

const whInn = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH inn',
    parameters: {
      httpMethod: 'POST',
      path: 'registration/crm-check-inn-v1',
      responseMode: 'responseNode',
      authentication: 'headerAuth',
      options: {},
    },
    position: [0, 480],
    ...headerAuthCred,
  },
  output: [{ body: { UF_INN: '1' }, headers: {} }],
});

const prepInn = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Map inn',
    parameters: {
      mode: 'runOnceForAllItems',
      language: 'javaScript',
      jsCode:
        "function fb(i){const j=i.json||{};return j.body!==undefined&&j.body!==null?j.body:j}const r=fb($input.first());const inn=String(r.INN||r.UF_INN||r.RQ_INN||'').trim();return[{json:{crmMethod:'crm.requisite.list',crmParams:{fields:[],params:[],select:['ID','RQ_INN','ENTITY_TYPE_ID','ENTITY_ID'],filter:{ENTITY_TYPE_ID:4,RQ_INN:inn}}}}];",
    },
    position: [260, 480],
    executeOnce: true,
  },
  output: [{ crmMethod: '', crmParams: {} }],
});

const whCo = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH co.add',
    parameters: {
      httpMethod: 'POST',
      path: 'registration/crm-company-add-v1',
      responseMode: 'responseNode',
      authentication: 'headerAuth',
      options: {},
    },
    position: [0, 720],
    ...headerAuthCred,
  },
  output: [{ body: { PARAMS: { fields: { TITLE: 'X' }, params: [] } }, headers: {} }],
});

const prepCo = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Map co',
    parameters: {
      mode: 'runOnceForAllItems',
      language: 'javaScript',
      jsCode:
        "function fb(i){const j=i.json||{};return j.body!==undefined&&j.body!==null?j.body:j}const b=fb($input.first());let p=b.PARAMS;if(!p||typeof p!=='object'){if(b.fields!=null){p={fields:b.fields,params:Array.isArray(b.params)?b.params:[]}}else{p={fields:{...b},params:[]}}}return[{json:{crmMethod:'crm.company.add',crmParams:p}}];",
    },
    position: [260, 720],
    executeOnce: true,
  },
  output: [{ crmMethod: '', crmParams: {} }],
});

const whCt = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH ct.add',
    parameters: {
      httpMethod: 'POST',
      path: 'registration/crm-contact-add-v1',
      responseMode: 'responseNode',
      authentication: 'headerAuth',
      options: {},
    },
    position: [0, 960],
    ...headerAuthCred,
  },
  output: [{ body: { PARAMS: { fields: { NAME: 'X' }, params: [] } }, headers: {} }],
});

const prepCt = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Map ct',
    parameters: {
      mode: 'runOnceForAllItems',
      language: 'javaScript',
      jsCode:
        "function fb(i){const j=i.json||{};return j.body!==undefined&&j.body!==null?j.body:j}const b=fb($input.first());let p=b.PARAMS;if(!p||typeof p!=='object'){if(b.fields!=null){p={fields:b.fields,params:Array.isArray(b.params)?b.params:[]}}else{p={fields:{...b},params:[]}}}return[{json:{crmMethod:'crm.contact.add',crmParams:p}}];",
    },
    position: [260, 960],
    executeOnce: true,
  },
  output: [{ crmMethod: '', crmParams: {} }],
});

const whAsync = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH async',
    parameters: {
      httpMethod: 'POST',
      path: 'registration/crm-register-post-sync-v1',
      responseMode: 'responseNode',
      authentication: 'none',
      options: {},
    },
    position: [0, 1200],
  },
  output: [{ body: { event: 'user_register_post_sync', site_user_id: 1 }, headers: {} }],
});

const ack = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Ack',
    parameters: {
      mode: 'runOnceForAllItems',
      language: 'javaScript',
      jsCode: 'const j=$input.first().json||{};return[{json:{success:1,accepted:true,echo:j.body??j}}];',
    },
    position: [260, 1200],
    executeOnce: true,
  },
  output: [{ success: 1 }],
});

const respA = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp async',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [520, 1200],
  },
  output: [{}],
});

const mIfBp = ifElse({
  version: 2.2,
  config: {
    name: 'M? p',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'm',
            leftValue: '={{ $json.crmMethod }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [540, 280],
  },
});

const pIfBp = ifElse({
  version: 2.2,
  config: {
    name: 'Pfx? p',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'p',
            leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [780, 280],
  },
});

const urlBp = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'URL p',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'u1',
            name: 'crmUrl',
            value: '={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
            type: 'string',
          },
        ],
      },
      includeOtherFields: true,
    },
    position: [980, 200],
  },
  output: [{ crmUrl: '', crmMethod: '', crmParams: {} }],
});

const encBp = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Enc p',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: flatEnc },
    position: [1180, 200],
    executeOnce: true,
  },
  output: [{ url: '', httpBody: '' }],
});

const postBp = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.4,
  config: {
    name: 'POST p',
    parameters: {
      method: 'POST',
      url: '={{ $json.url }}',
      sendBody: true,
      contentType: 'raw',
      body: '={{ $json.httpBody }}',
      rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      options: { response: { response: { responseFormat: 'json', neverError: true, fullResponse: false } } },
    },
    position: [1380, 200],
  },
  output: [{ result: null }],
});

const outBp = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Out p',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: shapeEnc },
    position: [1560, 200],
    executeOnce: true,
  },
  output: [{ success: 1, result: {} }],
});

const errPBp = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrP p',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          { id: 'p1', name: 'success', value: 0, type: 'number' },
          { id: 'p2', name: 'error', value: 'config', type: 'string' },
          { id: 'p3', name: 'message', value: 'EKLEKTIKA_B24_REST_PREFIX is empty', type: 'string' },
        ],
      },
      includeOtherFields: false,
    },
    position: [980, 360],
  },
  output: [{ success: 0 }],
});

const errMBp = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrM p',
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
    position: [980, 480],
  },
  output: [{ success: 0 }],
});

const respBp = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp p',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [1780, 280],
  },
  output: [{}],
});

const okBp = pIfBp.onTrue(urlBp.to(encBp).to(postBp).to(outBp)).to(respBp)).onFalse(errPBp.to(respBp));

const chainBp = parseProxy.to(mIfBp.onTrue(okBp).onFalse(errMBp.to(respBp)));

/** Регистрация: универсальный crm.* (JSON { METHOD, PARAMS }), без legacy UUID */
const whRegRest = trigger({
  type: 'n8n-nodes-base.webhook',
  version: 2.1,
  config: {
    name: 'WH reg REST',
    parameters: {
      httpMethod: 'POST',
      path: 'registration/crm-registration-rest-v1',
      responseMode: 'responseNode',
      authentication: 'headerAuth',
      options: {},
    },
    position: [0, 1360],
    ...headerAuthCred,
  },
  output: [{ body: { METHOD: 'crm.company.get', PARAMS: { id: 1 } }, headers: {} }],
});

const parseRegRest = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'Parse reg REST',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'r1',
            name: 'crmMethod',
            value: '={{ ($json.body && $json.body.METHOD) ? $json.body.METHOD : (($json.body && $json.body.method) ? $json.body.method : "") }}',
            type: 'string',
          },
          {
            id: 'r2',
            name: 'crmParams',
            value:
              '={{ ($json.body && $json.body.PARAMS !== undefined) ? $json.body.PARAMS : (($json.body && $json.body.params !== undefined) ? $json.body.params : {}) }}',
            type: 'object',
          },
        ],
      },
      includeOtherFields: false,
    },
    position: [260, 1360],
  },
  output: [{ crmMethod: '', crmParams: {} }],
});

const mIfBreg = ifElse({
  version: 2.2,
  config: {
    name: 'M? reg',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'm',
            leftValue: '={{ $json.crmMethod }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [540, 1400],
  },
});

const pIfBreg = ifElse({
  version: 2.2,
  config: {
    name: 'Pfx? reg',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'p',
            leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [780, 1400],
  },
});

const urlBreg = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'URL reg',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'u1',
            name: 'crmUrl',
            value: '={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
            type: 'string',
          },
        ],
      },
      includeOtherFields: true,
    },
    position: [980, 1320],
  },
  output: [{ crmUrl: '', crmMethod: '', crmParams: {} }],
});

const encBreg = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Enc reg',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: flatEnc },
    position: [1180, 1320],
    executeOnce: true,
  },
  output: [{ url: '', httpBody: '' }],
});

const postBreg = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.4,
  config: {
    name: 'POST reg',
    parameters: {
      method: 'POST',
      url: '={{ $json.url }}',
      sendBody: true,
      contentType: 'raw',
      body: '={{ $json.httpBody }}',
      rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      options: { response: { response: { responseFormat: 'json', neverError: true, fullResponse: false } } },
    },
    position: [1380, 1320],
  },
  output: [{ result: null }],
});

const outBreg = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Out reg',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: shapeEnc },
    position: [1560, 1320],
    executeOnce: true,
  },
  output: [{ success: 1, result: {} }],
});

const errPBreg = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrP reg',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          { id: 'p1', name: 'success', value: 0, type: 'number' },
          { id: 'p2', name: 'error', value: 'config', type: 'string' },
          { id: 'p3', name: 'message', value: 'EKLEKTIKA_B24_REST_PREFIX is empty', type: 'string' },
        ],
      },
      includeOtherFields: false,
    },
    position: [980, 1480],
  },
  output: [{ success: 0 }],
});

const errMBreg = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrM reg',
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
    position: [980, 1600],
  },
  output: [{ success: 0 }],
});

const respBreg = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp reg',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [1780, 1400],
  },
  output: [{}],
});

const okBreg = pIfBreg.onTrue(urlBreg.to(encBreg).to(postBreg).to(outBreg)).to(respBreg)).onFalse(errPBreg.to(respBreg));

const chainRegRest = parseRegRest.to(mIfBreg.onTrue(okBreg).onFalse(errMBreg.to(respBreg)));

const mIfBu = ifElse({
  version: 2.2,
  config: {
    name: 'M? u',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'm',
            leftValue: '={{ $json.crmMethod }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [540, 40],
  },
});

const pIfBu = ifElse({
  version: 2.2,
  config: {
    name: 'Pfx? u',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'p',
            leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [780, 40],
  },
});

const urlBu = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'URL u',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'u1',
            name: 'crmUrl',
            value: '={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
            type: 'string',
          },
        ],
      },
      includeOtherFields: true,
    },
    position: [980, -40],
  },
  output: [{ crmUrl: '', crmMethod: '', crmParams: {} }],
});

const encBu = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Enc u',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: flatEnc },
    position: [1180, -40],
    executeOnce: true,
  },
  output: [{ url: '', httpBody: '' }],
});

const postBu = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.4,
  config: {
    name: 'POST u',
    parameters: {
      method: 'POST',
      url: '={{ $json.url }}',
      sendBody: true,
      contentType: 'raw',
      body: '={{ $json.httpBody }}',
      rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      options: { response: { response: { responseFormat: 'json', neverError: true, fullResponse: false } } },
    },
    position: [1380, -40],
  },
  output: [{ result: null }],
});

const outBu = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Out u',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: shapeEnc },
    position: [1560, -40],
    executeOnce: true,
  },
  output: [{ success: 1, result: {} }],
});

const errPBu = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrP u',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          { id: 'p1', name: 'success', value: 0, type: 'number' },
          { id: 'p2', name: 'error', value: 'config', type: 'string' },
          { id: 'p3', name: 'message', value: 'EKLEKTIKA_B24_REST_PREFIX is empty', type: 'string' },
        ],
      },
      includeOtherFields: false,
    },
    position: [980, 120],
  },
  output: [{ success: 0 }],
});

const errMBu = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrM u',
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
    position: [980, 240],
  },
  output: [{ success: 0 }],
});

const respBu = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp u',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [1780, 40],
  },
  output: [{}],
});

const okBu = pIfBu.onTrue(urlBu.to(encBu).to(postBu).to(outBu)).to(respBu)).onFalse(errPBu.to(respBu));

const chainBu = prepU.to(mIfBu.onTrue(okBu).onFalse(errMBu.to(respBu)));

const mIfBi = ifElse({
  version: 2.2,
  config: {
    name: 'M? i',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'm',
            leftValue: '={{ $json.crmMethod }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [540, 520],
  },
});

const pIfBi = ifElse({
  version: 2.2,
  config: {
    name: 'Pfx? i',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'p',
            leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [780, 520],
  },
});

const urlBi = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'URL i',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'u1',
            name: 'crmUrl',
            value: '={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
            type: 'string',
          },
        ],
      },
      includeOtherFields: true,
    },
    position: [980, 440],
  },
  output: [{ crmUrl: '', crmMethod: '', crmParams: {} }],
});

const encBi = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Enc i',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: flatEnc },
    position: [1180, 440],
    executeOnce: true,
  },
  output: [{ url: '', httpBody: '' }],
});

const postBi = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.4,
  config: {
    name: 'POST i',
    parameters: {
      method: 'POST',
      url: '={{ $json.url }}',
      sendBody: true,
      contentType: 'raw',
      body: '={{ $json.httpBody }}',
      rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      options: { response: { response: { responseFormat: 'json', neverError: true, fullResponse: false } } },
    },
    position: [1380, 440],
  },
  output: [{ result: null }],
});

const outBi = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Out i',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: shapeEnc },
    position: [1560, 440],
    executeOnce: true,
  },
  output: [{ success: 1, result: {} }],
});

const errPBi = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrP i',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          { id: 'p1', name: 'success', value: 0, type: 'number' },
          { id: 'p2', name: 'error', value: 'config', type: 'string' },
          { id: 'p3', name: 'message', value: 'EKLEKTIKA_B24_REST_PREFIX is empty', type: 'string' },
        ],
      },
      includeOtherFields: false,
    },
    position: [980, 580],
  },
  output: [{ success: 0 }],
});

const errMBi = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrM i',
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
    position: [980, 700],
  },
  output: [{ success: 0 }],
});

const respBi = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp i',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [1780, 520],
  },
  output: [{}],
});

const okBi = pIfBi.onTrue(urlBi.to(encBi).to(postBi).to(outBi)).to(respBi)).onFalse(errPBi.to(respBi));

const chainBi = prepInn.to(mIfBi.onTrue(okBi).onFalse(errMBi.to(respBi)));

const mIfBc = ifElse({
  version: 2.2,
  config: {
    name: 'M? c',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'm',
            leftValue: '={{ $json.crmMethod }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [540, 760],
  },
});

const pIfBc = ifElse({
  version: 2.2,
  config: {
    name: 'Pfx? c',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'p',
            leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [780, 760],
  },
});

const urlBc = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'URL c',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'u1',
            name: 'crmUrl',
            value: '={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
            type: 'string',
          },
        ],
      },
      includeOtherFields: true,
    },
    position: [980, 680],
  },
  output: [{ crmUrl: '', crmMethod: '', crmParams: {} }],
});

const encBc = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Enc c',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: flatEnc },
    position: [1180, 680],
    executeOnce: true,
  },
  output: [{ url: '', httpBody: '' }],
});

const postBc = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.4,
  config: {
    name: 'POST c',
    parameters: {
      method: 'POST',
      url: '={{ $json.url }}',
      sendBody: true,
      contentType: 'raw',
      body: '={{ $json.httpBody }}',
      rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      options: { response: { response: { responseFormat: 'json', neverError: true, fullResponse: false } } },
    },
    position: [1380, 680],
  },
  output: [{ result: null }],
});

const outBc = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Out c',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: shapeEnc },
    position: [1560, 680],
    executeOnce: true,
  },
  output: [{ success: 1, result: {} }],
});

const errPBc = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrP c',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          { id: 'p1', name: 'success', value: 0, type: 'number' },
          { id: 'p2', name: 'error', value: 'config', type: 'string' },
          { id: 'p3', name: 'message', value: 'EKLEKTIKA_B24_REST_PREFIX is empty', type: 'string' },
        ],
      },
      includeOtherFields: false,
    },
    position: [980, 840],
  },
  output: [{ success: 0 }],
});

const errMBc = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrM c',
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
    position: [980, 960],
  },
  output: [{ success: 0 }],
});

const respBc = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp c',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [1780, 760],
  },
  output: [{}],
});

const okBc = pIfBc.onTrue(urlBc.to(encBc).to(postBc).to(outBc)).to(respBc)).onFalse(errPBc.to(respBc));

const chainBc = prepCo.to(mIfBc.onTrue(okBc).onFalse(errMBc.to(respBc)));

const mIfBt = ifElse({
  version: 2.2,
  config: {
    name: 'M? t',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'm',
            leftValue: '={{ $json.crmMethod }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [540, 1000],
  },
});

const pIfBt = ifElse({
  version: 2.2,
  config: {
    name: 'Pfx? t',
    parameters: {
      conditions: {
        combinator: 'and',
        options: { version: 2, caseSensitive: true, leftValue: '', typeValidation: 'strict' },
        conditions: [
          {
            id: 'p',
            leftValue: '={{ $env.EKLEKTIKA_B24_REST_PREFIX }}',
            rightValue: '',
            operator: { type: 'string', operation: 'notEmpty' },
          },
        ],
      },
    },
    position: [780, 1000],
  },
});

const urlBt = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'URL t',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          {
            id: 'u1',
            name: 'crmUrl',
            value: '={{ ($env.EKLEKTIKA_B24_REST_PREFIX + "").replace(/\\/+$/, "") + "/" + ($json.crmMethod + "").replace(/^\\/+/, "") + ".json" }}',
            type: 'string',
          },
        ],
      },
      includeOtherFields: true,
    },
    position: [980, 920],
  },
  output: [{ crmUrl: '', crmMethod: '', crmParams: {} }],
});

const encBt = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Enc t',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: flatEnc },
    position: [1180, 920],
    executeOnce: true,
  },
  output: [{ url: '', httpBody: '' }],
});

const postBt = node({
  type: 'n8n-nodes-base.httpRequest',
  version: 4.4,
  config: {
    name: 'POST t',
    parameters: {
      method: 'POST',
      url: '={{ $json.url }}',
      sendBody: true,
      contentType: 'raw',
      body: '={{ $json.httpBody }}',
      rawContentType: 'application/x-www-form-urlencoded; charset=UTF-8',
      options: { response: { response: { responseFormat: 'json', neverError: true, fullResponse: false } } },
    },
    position: [1380, 920],
  },
  output: [{ result: null }],
});

const outBt = node({
  type: 'n8n-nodes-base.code',
  version: 2,
  config: {
    name: 'Out t',
    parameters: { mode: 'runOnceForAllItems', language: 'javaScript', jsCode: shapeEnc },
    position: [1560, 920],
    executeOnce: true,
  },
  output: [{ success: 1, result: {} }],
});

const errPBt = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrP t',
    parameters: {
      mode: 'manual',
      assignments: {
        assignments: [
          { id: 'p1', name: 'success', value: 0, type: 'number' },
          { id: 'p2', name: 'error', value: 'config', type: 'string' },
          { id: 'p3', name: 'message', value: 'EKLEKTIKA_B24_REST_PREFIX is empty', type: 'string' },
        ],
      },
      includeOtherFields: false,
    },
    position: [980, 1120],
  },
  output: [{ success: 0 }],
});

const errMBt = node({
  type: 'n8n-nodes-base.set',
  version: 3.4,
  config: {
    name: 'ErrM t',
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
    position: [980, 1240],
  },
  output: [{ success: 0 }],
});

const respBt = node({
  type: 'n8n-nodes-base.respondToWebhook',
  version: 1.5,
  config: {
    name: 'Resp t',
    parameters: { respondWith: 'json', responseBody: '={{ $json }}', options: { responseCode: 200 } },
    position: [1780, 1000],
  },
  output: [{}],
});

const okBt = pIfBt.onTrue(urlBt.to(encBt).to(postBt).to(outBt)).to(respBt)).onFalse(errPBt.to(respBt));

const chainBt = prepCt.to(mIfBt.onTrue(okBt).onFalse(errMBt.to(respBt)));

sticky(
  [
    'Регистрация → n8n (без legacy UUID)',
    '',
    '【1】WH unique — первый запрос: OnBefore, email/телефон (crm.contact.list). Header Auth.',
    '【2】WH reg REST — все прочие crm.* из RegisterUserCompany (company.get/update, requisite.*, contact.company.* …): JSON { METHOD, PARAMS }. Конфиг PHP: registration_crm_rest_proxy_webhook_url. Header Auth.',
    '【3】WH inn — ИНН → crm.requisite.list (если включена отдельная цепочка). Header Auth.',
    '【4】WH company.add — создание компании. Header Auth.',
    '【5】WH contact.add — создание контакта. Header Auth.',
    '【6】WH async — пост-регистрация ACK; без Header Auth.',
    '',
    'Legacy WH (UUID) — только для глобального RestClient / n8n_crm_rest_proxy_webhook_url, не для RegisterUserCompany.',
    'Секрет: X-Sync-Token = inbound_secret (кроме async).',
  ].join('\n'),
  [whU, whRegRest],
  { color: 4 },
);

sticky(
  ['【1】Первый по сценарию регистрации', 'До создания пользователя на сайте. Path: registration/crm-check-unique-contact-v1'].join('\n'),
  [whU],
  { color: 3 },
);

sticky(
  [
    '【2】Регистрация: универсальный CRM REST',
    'Только RegisterUserCompany → конфиг registration_crm_rest_proxy_webhook_url.',
    'Path: registration/crm-registration-rest-v1',
  ].join('\n'),
  [whRegRest],
  { color: 6 },
);

sticky(
  [
    'Legacy CRM proxy (вне регистрации)',
    'Глобальный RestClient при n8n_crm_rest_proxy_webhook_url или env EKLEKTIKA_N8N_CRM_WEBHOOK_URL.',
    'UUID path — временно, пока остальной код не переведён на именованные вебхуки.',
  ].join('\n'),
  [whProx],
  { color: 2 },
);

sticky(
  ['【3】Проверка ИНН в CRM', 'crm.requisite.list по RQ_INN; вызывается из createB24Company при необходимости.'].join('\n'),
  [whInn],
  { color: 3 },
);

sticky(
  ['【4】Создание компании', 'crm.company.add из PHP после ветвления по ИНН/локальной связке.'].join('\n'),
  [whCo],
  { color: 3 },
);

sticky(
  ['【5】Создание контакта', 'crm.contact.add; связка contact↔company через 【2】WH reg REST (crm.contact.company.add).'].join(
    '\n',
  ),
  [whCt],
  { color: 3 },
);

sticky(
  ['【6】Async post-register', 'Фон после успешной регистрации на сайте (async_post_register в конфиге).'].join('\n'),
  [whAsync],
  { color: 5 },
);

export default workflow('gGtsrfCPP9t3OyLj', 'Site to CRM')
  .add(whProx)
  .to(chainBp)
  .add(whRegRest)
  .to(chainRegRest)
  .add(whU)
  .to(chainBu)
  .add(whInn)
  .to(chainBi)
  .add(whCo)
  .to(chainBc)
  .add(whCt)
  .to(chainBt)
  .add(whAsync)
  .to(ack.to(respA));
