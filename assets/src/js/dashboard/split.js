// import * as React from "react";
const { useState } = wp.element;
// import { useState } from "react"
import swal from 'sweetalert';


const loadPreviouses = {count: 0, active: true};
let repeatableSteps = 0;
// 
const Field = ({ row }) => {
  row = {
    index           : row?.index??repeatableSteps,
    comission       : 0,
    account_id      : '',
    comission_type  : '',
    ...row
  };
  const [account_id, setAccount_id] = useState(row.account_id);
  const [comission, setComission] = useState(row.comission);
  const [comission_type, setComission_type] = useState(row.comission_type);
  const handleChange = (e, type) => {
    switch (type) {
      case 'account_id':
        setAccount_id(e.target.value);
        break;
      case 'comission_type':
        setComission_type(e.target.value);
        break;
      case 'comission':
        setComission(e.target.value);
        break;
      default:
        break;
    }
  };
  const handleClick = (e, type) => {
    e.preventDefault();
    // 
    switch (type) {
      case 'trash':
        swal("Are you sure you want to remove this?", {
          buttons: {
            cancel: "Cancel",
            catch: {
              text: "I Confirm it!",
              value: "confirm",
            },
            defeat: false,
          },
        }).then((value) => {
          switch (value) {
            case "defeat":
              // swal("Pikachu fainted! You gained 500 XP!");
              break;
            case "confirm":
              // swal("Gotcha!", "Pikachu was caught!", "success");
              e.target.parentElement.parentElement.remove();
              break;
            default:
              // swal("Got away safely!");
          }
        });
        break;
      default:
        break;
    }
  };
  console.log(row)
  return (
    <div class="splitacc__single">
      <div class="splitacc__trash">
        <span class="splitacc__trash__icon" onClick={e => handleClick(e, 'trash')}>
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M9.1709 4C9.58273 2.83481 10.694 2 12.0002 2C13.3064 2 14.4177 2.83481 14.8295 4" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path><path d="M20.5001 6H3.5" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path><path d="M18.8332 8.5L18.3732 15.3991C18.1962 18.054 18.1077 19.3815 17.2427 20.1907C16.3777 21 15.0473 21 12.3865 21H11.6132C8.95235 21 7.62195 21 6.75694 20.1907C5.89194 19.3815 5.80344 18.054 5.62644 15.3991L5.1665 8.5" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path><path d="M9.5 11L10 16" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path><path d="M14.5 11L14 16" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path></g></svg>
        </span>
      </div>
      <div class="splitacc__block">
        <label htmlFor={`field${row?.id??''}`}>Account ID</label>
        <input class="dokan-form-control" id={`field${row?.index}`} type="text" name={`settings[flutterwave][split_accounts][${row.index}][account_id]`} value={account_id} onChange={e => handleChange(e, 'account_id')}/>
      </div>
      <div class="splitacc__block">
        <label htmlFor={`field${row?.index}`}>Commission type</label>
        <select class="dokan-form-control" id={`field${row?.index}`} name={`settings[flutterwave][split_accounts][${row.index}][comission_type]`} defaultValue={comission_type}>
          {[
            ['percentage', 'Percentage'],
            ['fixed', 'Fixed Amount'],
          ].map(row => (
            <option value={row[0]} key={row[0]}>{row[1]}</option>
          ))}
        </select>
      </div>
      <div class="splitacc__block">
        <label htmlFor={`field${row?.index}`}>Commission</label>
        <input class="dokan-form-control" id={`field${row?.index}`} type="number" name={`settings[flutterwave][split_accounts][${row.index}][comission]`} step="any" value={comission} onChange={e => handleChange(e, 'comission')}/>
      </div>
    </div>
  );
};
// 
const Form = (data) => {
  const [divList, setDivList] = useState([]);
  window.divList = divList;
  // 
  if (loadPreviouses?.active) {
    const stored = Object.values(data?.stored??[]);
    console.log(stored);
    stored.forEach(async (row, index) => {
      repeatableSteps++;row.index = index;
      setDivList(divList.concat(<Field row={row} />));
      // setDivList(divList.push(<Field row={row} />));
      loadPreviouses.count++;
      if (stored.length > loadPreviouses.count) {
        loadPreviouses.active = false;
      }
      console.log(<Field row={row} />, row);
    });
  }
  // 
  const onAddBtnClick = (event) => {
    repeatableSteps++;
    const row = {index: repeatableSteps};
    setDivList(divList.concat(<Field row={row} />));
  };
  // 
  return (
    <div class="splitacc__block">
      <div class="splitacc__row">{divList}</div>
      <button onClick={onAddBtnClick} type="button" class="splitacc__repeater">Add Sub Account</button>
    </div>
  );
};
// 
export default Form;