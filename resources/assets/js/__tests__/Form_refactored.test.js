import Form from  '../components/form/Form_refactored';


function patchedLayout(layout) {
    let curFieldsetId = 0;
    let mapFieldLayout = layout => layout.legend ? {
            ...layout,
            id: `${curFieldsetId++}#${layout.legend}`,
        } : layout;
    return {
        tabs: layout.tabs.map(tab => ({
            ...tab,
            columns: tab.columns.map(column => ({
                ...column,
                fields: column.fields.map(mapFieldLayout)
            }))
        })),
    };
}

describe('Form_refactored', ()=>{
    test('patched layout', ()=>{
        expect(patchedLayout({
            tabs: [{
                columns: [
                    {
                        fields: [{
                            legend: 'aaa',
                            fields: [
                                { id: 2 }
                            ]
                        }, {
                            id: 1,
                        }],
                    }
                ]
            }]
        })).toEqual({
            tabs: [{
                columns: [
                    {
                        fields: [{
                            id: '0#aaa',
                            legend: 'aaa',
                            fields: [
                                { id: 2 }
                            ]
                        }, {
                            id: 1,
                        }],
                    }
                ]
            }]
        })
    });
});